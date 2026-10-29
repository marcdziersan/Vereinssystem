<?php
/** InnoDB storage: CAS revisions, transactional audit, bound parameters, no credentials. */
declare(strict_types=1);
namespace Vereinssystem\Settings;

use DomainException;
use PDO;
use PDOException;
use RuntimeException;
use Vereinssystem\Core\Database;

final class PdoSettingsRepository implements SettingsRepository
{
    public function __construct(private readonly Database $database) {}

    public function load():array
    {
        $statement=$this->database->connection()->prepare(
            'SELECT values_json,revision FROM vs_system_settings WHERE id=1 LIMIT 1'
        );
        $statement->execute();
        $row=$statement->fetch(PDO::FETCH_ASSOC);
        return $row ? ['revision'=>(int)$row['revision'],'values'=>self::decode((string)$row['values_json'])]
            : ['revision'=>0,'values'=>[]];
    }

    /** @return array<string,string> */
    private static function decode(string $json):array
    {
        $data=json_decode($json,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($data)||array_is_list($data))throw new RuntimeException('Einstellungen enthalten ungültige Daten.');
        foreach($data as $k=>$v){
            if(!is_string($k)||!is_string($v)||!array_key_exists($k,SettingsService::DEFAULTS))
                throw new RuntimeException('Unbekannter oder ungültiger Eintrag in Systemeinstellungen.');
        }
        return $data;
    }

    public function save(array $values,int $expectedRevision,int $actorId):int
    {
        if($actorId<1||$expectedRevision<0||$expectedRevision===PHP_INT_MAX)
            throw new DomainException('Ungültige Administrator-ID oder Versionsnummer.');
        $pdo=$this->database->connection();
        $pdo->beginTransaction();
        try{
            $query=$pdo->prepare('SELECT values_json,revision FROM vs_system_settings WHERE id=1 FOR UPDATE');
            $query->execute();
            $row=$query->fetch(PDO::FETCH_ASSOC);
            $actual=$row?(int)$row['revision']:0;
            if($actual!==$expectedRevision)throw new DomainException('Die Einstellungen wurden zwischenzeitlich geändert. Bitte neu laden.');
            $before=$row?self::decode((string)$row['values_json']):SettingsService::DEFAULTS;
            $after=$values;
            // Defend against accidental extra fields even if the caller bypasses the service.
            if(array_keys($after)!==array_keys(SettingsService::DEFAULTS))
                throw new DomainException('Einstellungsschlüssel sind nicht vollständig oder unbekannt.');
            $changes=[];
            foreach($after as $key=>$value){
                if(!is_string($value))throw new DomainException('Ungültiger Einstellungstyp.');
                $original=$before[$key]??SettingsService::DEFAULTS[$key];
                if($original!==$value)$changes[$key]=['before'=>$original,'after'=>$value];
            }
            if(!$changes){$pdo->commit();return $actual;}
            $next=$actual+1;
            $json=json_encode($after,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            if($row){
                $sql='UPDATE vs_system_settings SET values_json=?,revision=?,updated_by=?,updated_at=NOW() WHERE id=1 AND revision=?';
                $cmd=$pdo->prepare($sql);
                $cmd->execute([$json,$next,$actorId,$actual]);
                if($cmd->rowCount()!==1)throw new DomainException('Speicherkonflikt; bitte neu laden.');
            }else{
                $cmd=$pdo->prepare('INSERT INTO vs_system_settings(id,values_json,revision,updated_by,updated_at) VALUES(1,?,?,?,NOW())');
                try{$cmd->execute([$json,$next,$actorId]);}
                catch(PDOException $e){
                    if((string)$e->getCode()==='23000')throw new DomainException('Speicherkonflikt; bitte neu laden.',0,$e);
                    throw $e;
                }
            }
            $audit=$pdo->prepare('INSERT INTO vs_system_audit(actor_id,revision,changes_json) VALUES(?,?,?)');
            $audit->execute([$actorId,$next,json_encode($changes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
            $pdo->commit();
            return $next;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

    public function history(int $limit=30,int $offset=0):array
    {
        $limit=max(1,min(100,$limit));$offset=max(0,min(100000,$offset));
        $statement=$this->database->connection()->prepare(
            'SELECT id,actor_id,revision,changes_json,created_at FROM vs_system_audit ORDER BY id DESC LIMIT ? OFFSET ?'
        );
        $statement->bindValue(1,$limit,PDO::PARAM_INT);
        $statement->bindValue(2,$offset,PDO::PARAM_INT);
        $statement->execute();
        $result=[];
        foreach($statement->fetchAll(PDO::FETCH_ASSOC) as $row){
            $changes=json_decode((string)$row['changes_json'],true,512,JSON_THROW_ON_ERROR);
            if(!is_array($changes))throw new RuntimeException('Systemprotokoll ungültig.');
            $result[]=[
                'id'=>(int)$row['id'],'actor_id'=>(int)$row['actor_id'],
                'revision'=>(int)$row['revision'],'changes'=>$changes,
                'created_at'=>(string)$row['created_at'],
            ];
        }
        return $result;
    }
}
