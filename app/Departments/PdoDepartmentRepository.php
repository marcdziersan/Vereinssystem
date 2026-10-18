<?php
/** SQL implementation using the existing `abteilungen` and `abteilungszugehoerigkeit` schema. */
declare(strict_types=1);
namespace Vereinssystem\Departments;
use PDO;
use Vereinssystem\Core\Database;

final class PdoDepartmentRepository implements DepartmentRepository
{
    public function __construct(private readonly Database $db) {}

    public function all(bool $includeInactive=false): array
    {
        $sql='SELECT a.abteilungs_id,a.name,a.beschreibung,a.homepage,a.abteilungsleiter,a.aktivenumlage,a.status, (SELECT COUNT(*) FROM abteilungszugehoerigkeit z WHERE z.abteilung=a.abteilungs_id) AS member_count FROM abteilungen a';
        if (!$includeInactive) $sql.=' WHERE a.status=1';
        $sql.=' ORDER BY a.name, a.abteilungs_id LIMIT 500';
        $stmt=$this->db->connection()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function find(int $id): ?array
    {
        $s=$this->db->connection()->prepare('SELECT * FROM abteilungen WHERE abteilungs_id=?');$s->execute([$id]);return $s->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public function members(int $id): array
    {
        $s=$this->db->connection()->prepare('SELECT z.mitglied,z.aktiv,z.beitrittdate,m.vorname,m.nachname,m.mitgliedsnummer,m.status FROM abteilungszugehoerigkeit z INNER JOIN mitglieder m ON m.mitglieder_id=z.mitglied WHERE z.abteilung=? ORDER BY m.nachname,m.vorname LIMIT 1000');
        $s->execute([$id]);return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    public function save(array $values,?int $id): int
    {
        $args=[$values['name'],$values['beschreibung'],$values['homepage'],$values['abteilungsleiter'],$values['aktivenumlage']];
        if ($id===null) {
            $s=$this->db->connection()->prepare('INSERT INTO abteilungen (name,beschreibung,homepage,abteilungsleiter,aktivenumlage,status) VALUES (?,?,?,?,?,1)');
            $s->execute($args);return (int)$this->db->connection()->lastInsertId();
        }
        $s=$this->db->connection()->prepare('UPDATE abteilungen SET name=?,beschreibung=?,homepage=?,abteilungsleiter=?,aktivenumlage=? WHERE abteilungs_id=? AND status=1');
        $s->execute([...$args,$id]);return $id;
    }
    public function assign(int $department,int $member,int $active,string $since): void
    {
        $pdo=$this->db->connection();$pdo->beginTransaction();
        try {
            // Verify both foreign-domain records, including active status, before write.
            $check=$pdo->prepare('SELECT 1 FROM abteilungen WHERE abteilungs_id=? AND status=1 FOR UPDATE');
            $check->execute([$department]);if(!$check->fetchColumn())throw new \DomainException('Abteilung ist nicht aktiv.');
            $check=$pdo->prepare('SELECT 1 FROM mitglieder WHERE mitglieder_id=? AND status=1 FOR UPDATE');
            $check->execute([$member]);if(!$check->fetchColumn())throw new \DomainException('Mitglied nicht aktiv oder nicht vorhanden.');
            $stmt=$pdo->prepare('INSERT INTO abteilungszugehoerigkeit (mitglied,abteilung,aktiv,beitrittdate) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE aktiv=VALUES(aktiv),beitrittdate=VALUES(beitrittdate)');
            $stmt->execute([$member,$department,$active,$since]);$pdo->commit();
        } catch(\Throwable $e) {$pdo->rollBack();throw $e;}
    }
    public function unassign(int $department,int $member): bool
    {
        $s=$this->db->connection()->prepare('DELETE FROM abteilungszugehoerigkeit WHERE abteilung=? AND mitglied=?');
        $s->execute([$department,$member]);return $s->rowCount()===1;
    }
    public function setStatus(int $id,int $status): bool
    {
        $s=$this->db->connection()->prepare('UPDATE abteilungen SET status=? WHERE abteilungs_id=? AND status<>?');
        $s->execute([$status,$id,$status]);return $s->rowCount()===1;
    }
}
