<?php
/**
 * PDO-Persistenz für Veranstaltungen und freie Aufgabenplanung.
 * Jeder Schreibvorgang zur Besetzung läuft in einer Transaktion. Mitgliedszeilen
 * werden in sortierter Reihenfolge gesperrt, damit konkurrierende Zuteilungen
 * nicht unbemerkt denselben Zeitraum belegen.
 */
declare(strict_types=1);
namespace Vereinssystem\Events;

use PDO;
use Vereinssystem\Core\Database;
use Vereinssystem\Models\{Event,EventTask};

final class PdoEventRepository implements EventRepository
{
    public function __construct(private readonly Database $database) {}

    public function listing(bool $includeInactive=false): array
    {
        $sql='SELECT v.veranstaltungs_id,v.veranstaltungsname,v.startzeit,v.endzeit,v.status,
            o.name AS ortsname,
            (SELECT COUNT(*) FROM vs_event_aufgaben a WHERE a.event_id=v.veranstaltungs_id AND a.status=1) AS task_count
            FROM veranstaltungen v LEFT JOIN orte o ON o.orts_id=v.ort';
        if(!$includeInactive) $sql.=' WHERE v.status=1';
        $sql.=' ORDER BY v.startzeit DESC,v.veranstaltungs_id DESC LIMIT 300';
        return $this->database->connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt=$this->database->connection()->prepare('SELECT * FROM veranstaltungen WHERE veranstaltungs_id=?');
        $stmt->execute([$id]);return $stmt->fetch(PDO::FETCH_ASSOC)?:null;
    }

    public function save(Event $event): int
    {
        $values=$event->values();
        $id=$event->id;
        $pdo=$this->database->connection();
        $args=[$values['veranstaltungsname'],$values['beschreibung'],$values['ort'],
            $values['ansprechpartner'],$values['startzeit'],$values['endzeit']];
        $pdo->beginTransaction();
        try {
            $this->assertRelatedRecords($pdo,$values);
            if($id===null){
                $stmt=$pdo->prepare('INSERT INTO veranstaltungen
                    (veranstaltungsname,beschreibung,ort,ansprechpartner,startzeit,endzeit,mindienste,minkuchen,dienstbeschreibung,status)
                    VALUES (?,?,?,?,?,?,0,0,\'\',1)');
                $stmt->execute($args);
                $id=(int)$pdo->lastInsertId();
            } else {
                $stmt=$pdo->prepare('UPDATE veranstaltungen SET veranstaltungsname=?,beschreibung=?,ort=?,
                    ansprechpartner=?,startzeit=?,endzeit=? WHERE veranstaltungs_id=? AND status=1');
                $stmt->execute([...$args,$id]);
                if($stmt->rowCount()===0){
                    $check=$pdo->prepare('SELECT status FROM veranstaltungen WHERE veranstaltungs_id=?');
                    $check->execute([$id]);
                    if((int)$check->fetchColumn()!==1)throw new \DomainException('Veranstaltung nicht aktiv.');
                }
            }
            $pdo->commit();return $id;
        } catch(\Throwable $e) { $pdo->rollBack();throw $e; }
    }

    private function assertRelatedRecords(PDO $pdo,array $values): void
    {
        foreach([['ort','orte','orts_id'],['ansprechpartner','mitglieder','mitglieder_id']] as [$key,$table,$column]){
            $id=(int)$values[$key];if($id===0)continue;
            $stmt=$pdo->prepare("SELECT status FROM `$table` WHERE `$column`=?");
            $stmt->execute([$id]);
            if((int)$stmt->fetchColumn()!==1)throw new \DomainException('Ort oder Ansprechpartner nicht aktiv.');
        }
    }

    public function setStatus(int $id,int $status): bool
    {
        $stmt=$this->database->connection()->prepare('UPDATE veranstaltungen SET status=? WHERE veranstaltungs_id=? AND status<>?');
        $stmt->execute([$status,$id,$status]);return $stmt->rowCount()===1;
    }

    /** Gruppierte Aufgaben mit den zugeteilten Mitgliedern. Keine ID-Eingabefelder nötig. */
    public function tasks(int $eventId): array
    {
        $stmt=$this->database->connection()->prepare('SELECT a.*, z.mitglied_id, m.vorname, m.nachname
            FROM vs_event_aufgaben a
            LEFT JOIN vs_event_aufgaben_mitglieder z ON z.aufgabe_id=a.id
            LEFT JOIN mitglieder m ON m.mitglieder_id=z.mitglied_id
            WHERE a.event_id=? AND a.status=1
            ORDER BY a.startzeit,a.id,m.nachname,m.vorname');
        $stmt->execute([$eventId]);$tasks=[];
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            $id=(int)$row['id'];
            if(!isset($tasks[$id])){$row['members']=[];$tasks[$id]=$row;}
            if($row['mitglied_id']!==null){
                $tasks[$id]['members'][]=['id'=>(int)$row['mitglied_id'],
                    'name'=>trim((string)$row['vorname'].' '.(string)$row['nachname'])];
            }
        }
        return array_values($tasks);
    }

    public function task(int $eventId,int $taskId): ?array
    {
        foreach($this->tasks($eventId) as $task){if((int)$task['id']===$taskId)return $task;}
        return null;
    }

    public function activeMembers(): array
    {
        $stmt=$this->database->connection()->query('SELECT mitglieder_id,vorname,nachname FROM mitglieder
            WHERE status=1 ORDER BY nachname,vorname,mitglieder_id LIMIT 2000');
        return array_map(static fn(array $r):array=>['id'=>(int)$r['mitglieder_id'],
            'name'=>trim((string)$r['vorname'].' '.(string)$r['nachname'])],$stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return list<array{id:int,name:string}> */
    public function activePlaces(): array
    {
        $query = $this->database->connection()->query('SELECT orts_id, name FROM orte WHERE status=1 ORDER BY name,orts_id LIMIT 1000');
        return array_map(static fn(array $row): array => [
            'id'=>(int)$row['orts_id'], 'name'=>(string)$row['name'],
        ], $query->fetchAll(PDO::FETCH_ASSOC));
    }

    public function saveTask(EventTask $task,int $actorId): int
    {
        $eventId=$task->eventId;
        $taskId=$task->id;
        $memberIds=$task->memberIds();
        $pdo=$this->database->connection();$pdo->beginTransaction();
        try {
            $stmt=$pdo->prepare('SELECT status FROM veranstaltungen WHERE veranstaltungs_id=? FOR UPDATE');
            $stmt->execute([$eventId]);
            if((int)$stmt->fetchColumn()!==1)throw new \DomainException('Veranstaltung nicht aktiv.');
            if($taskId!==null){
                $stmt=$pdo->prepare('SELECT status FROM vs_event_aufgaben WHERE id=? AND event_id=? FOR UPDATE');
                $stmt->execute([$taskId,$eventId]);
                if((int)$stmt->fetchColumn()!==1)throw new \DomainException('Aufgabe nicht verfügbar.');
            }
            // Member row locking serializes assignments from concurrent HTTP requests.
            foreach($memberIds as $id){
                $stmt=$pdo->prepare('SELECT status FROM mitglieder WHERE mitglieder_id=? FOR UPDATE');
                $stmt->execute([$id]);
                if((int)$stmt->fetchColumn()!==1)throw new \DomainException('Ausgewähltes Mitglied ist inaktiv oder nicht vorhanden.');
                $stmt=$pdo->prepare('SELECT COUNT(*) FROM vs_event_aufgaben_mitglieder z
                    INNER JOIN vs_event_aufgaben a ON a.id=z.aufgabe_id
                    INNER JOIN veranstaltungen v ON v.veranstaltungs_id=a.event_id
                    WHERE z.mitglied_id=? AND a.status=1 AND v.status=1 AND a.startzeit<? AND a.endzeit>?
                      AND a.id<>?');
                $stmt->execute([$id,$task->endsAt,$task->startsAt,$taskId??0]);
                if((int)$stmt->fetchColumn()>0)throw new \DomainException('Mindestens ein Mitglied ist zu dieser Zeit bereits einer anderen Aufgabe zugeteilt.');
            }
            $args=[$task->title,$task->description,$task->place,$task->startsAt,$task->endsAt];
            if($taskId===null){
                $stmt=$pdo->prepare('INSERT INTO vs_event_aufgaben
                    (event_id,titel,beschreibung,ort,startzeit,endzeit,status,erstellt_von)
                    VALUES (?,?,?,?,?,?,1,?)');
                $stmt->execute([$eventId,...$args,$actorId]);$taskId=(int)$pdo->lastInsertId();
            }else{
                $stmt=$pdo->prepare('UPDATE vs_event_aufgaben SET titel=?,beschreibung=?,ort=?,startzeit=?,endzeit=?
                    WHERE id=? AND event_id=? AND status=1');
                $stmt->execute([...$args,$taskId,$eventId]);
                $pdo->prepare('DELETE FROM vs_event_aufgaben_mitglieder WHERE aufgabe_id=?')->execute([$taskId]);
            }
            $stmt=$pdo->prepare('INSERT INTO vs_event_aufgaben_mitglieder
                (aufgabe_id,mitglied_id,zugeteilt_von) VALUES (?,?,?)');
            foreach($memberIds as $id)$stmt->execute([$taskId,$id,$actorId]);
            $pdo->commit();return $taskId;
        }catch(\Throwable $e){$pdo->rollBack();throw $e;}
    }

    public function archiveTask(int $eventId,int $taskId): void
    {
        $stmt=$this->database->connection()->prepare('UPDATE vs_event_aufgaben SET status=2
            WHERE id=? AND event_id=? AND status=1');
        $stmt->execute([$taskId,$eventId]);
        if($stmt->rowCount()!==1)throw new \DomainException('Aufgabe nicht mehr vorhanden.');
    }
}
