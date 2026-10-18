<?php
/** Parameterized PDO adapter for legacy `orte` columns. */
declare(strict_types=1);
namespace Vereinssystem\Places;

use PDO;
use Vereinssystem\Core\Database;

final class PdoPlaceRepository implements PlaceRepository
{
    public function __construct(private readonly Database $database) {}

    public function all(bool $includeInactive = false): array
    {
        $sql = 'SELECT orts_id, name, strasse, hausnummer, plz, ort, telefon, typ, createdby, status FROM orte';
        if (!$includeInactive) $sql .= ' WHERE status = 1';
        $stmt = $this->database->connection()->prepare($sql.' ORDER BY ort, strasse, hausnummer, orts_id LIMIT 1000');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->database->connection()->prepare('SELECT * FROM orte WHERE orts_id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function save(array $values, ?int $id, int $actor): int
    {
        $fields = ['name','strasse','hausnummer','plz','ort','telefon','typ'];
        $args = array_map(static fn(string $key): string|int => $values[$key], $fields);
        if ($id === null) {
            $stmt = $this->database->connection()->prepare('INSERT INTO orte (name,strasse,hausnummer,plz,ort,telefon,typ,createdby,status) VALUES (?,?,?,?,?,?,?, ?,1)');
            $stmt->execute([...$args,$actor]);
            return (int)$this->database->connection()->lastInsertId();
        }
        $stmt = $this->database->connection()->prepare('UPDATE orte SET name=?,strasse=?,hausnummer=?,plz=?,ort=?,telefon=?,typ=? WHERE orts_id=? AND status=1');
        $stmt->execute([...$args,$id]);
        return $id;
    }

    public function setStatus(int $id, int $status): bool
    {
        $stmt = $this->database->connection()->prepare('UPDATE orte SET status=? WHERE orts_id=? AND status<>?');
        $stmt->execute([$status,$id,$status]);
        return $stmt->rowCount()===1;
    }
}
