<?php
/**
 * SQL-only member persistence, compatible with the 2013 `mitglieder` schema.
 *
 * No SQL compatibility mode, `db_escape()` or concatenated request values.
 * Bank data and password hashes are deliberately excluded from member reads.
 */
declare(strict_types=1);

namespace Vereinssystem\Members;

use PDO;
use PDOException;
use Vereinssystem\Core\Database;

final class PdoMemberRepository implements MemberRepository
{
    private const FIELDS = 'mitglieder_id, mitgliedsnummer, vorname, nachname, geburtstag, beitritt, '
        . 'email, handy, geschlecht, mitgliedschaft, abrechnung, anschrift, parent1, parent2, '
        . 'notizen, username, rights, status';

    public function __construct(private readonly Database $database) {}

    public function byId(int $id): ?Member
    {
        $statement = $this->database->connection()->prepare(
            'SELECT '.self::FIELDS.' FROM mitglieder WHERE mitglieder_id = ? LIMIT 1'
        );
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? self::fromRow($row) : null;
    }

    public function statistics(): array
    {
        $sql = 'SELECT COUNT(*) AS total, '
            . 'COALESCE(SUM(CASE WHEN mitgliedschaft = 0 THEN 1 ELSE 0 END),0) AS ordinary, '
            . 'COALESCE(SUM(CASE WHEN mitgliedschaft = 1 THEN 1 ELSE 0 END),0) AS supporting, '
            . 'COALESCE(SUM(CASE WHEN mitgliedschaft = 2 THEN 1 ELSE 0 END),0) AS resting '
            . 'FROM mitglieder WHERE status = 1';
        $data = $this->database->connection()->query($sql)->fetch(PDO::FETCH_ASSOC);
        return array_map('intval', $data ?: ['total'=>0,'ordinary'=>0,'supporting'=>0,'resting'=>0]);
    }

    /** @return array{items:list<Member>,total:int,page:int,pages:int} */
    public function search(string $term, int $page = 1, int $perPage = 20, string $status = 'active'): array
    {
        $page = max(1, min(100000, $page));
        $perPage = max(1, min(50, $perPage));
        $filter = $status === 'inactive' ? 'status = 2' : 'status = 1';
        $where = ' WHERE '.$filter;
        $params = [];
        if ($term !== '') {
            // Escape SQL-LIKE wildcards; user input is still always bound.
            $needle = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
            $where .= " AND (vorname LIKE ? ESCAPE '!' OR nachname LIKE ? ESCAPE '!' "
                . "OR mitgliedsnummer LIKE ? ESCAPE '!' OR username LIKE ? ESCAPE '!')";
            $params = [$needle, $needle, $needle, $needle];
        }
        $db = $this->database->connection();
        $count = $db->prepare('SELECT COUNT(*) FROM mitglieder'.$where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $sql = 'SELECT '.self::FIELDS.' FROM mitglieder'.$where
            . ' ORDER BY nachname ASC, vorname ASC, mitglieder_id ASC LIMIT ? OFFSET ?';
        $list = $db->prepare($sql);
        foreach ($params as $i=>$value) $list->bindValue($i + 1, $value, PDO::PARAM_STR);
        $list->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
        $list->bindValue(count($params) + 2, ($page - 1) * $perPage, PDO::PARAM_INT);
        $list->execute();
        return [
            'items'=>array_map(self::fromRow(...), $list->fetchAll(PDO::FETCH_ASSOC)),
            'total'=>$total, 'page'=>$page, 'pages'=>$pages,
        ];
    }

    public function create(array $data, string $passwordHash): int
    {
        // Every NOT NULL legacy column is explicitly provided. No rights are
        // accepted from browser input: a new account always starts at level 1.
        $values = [
            'mitgliedsnummer'=>$data['number'], 'nachname'=>$data['last_name'],
            'vorname'=>$data['first_name'], 'geschlecht'=>$data['gender'],
            'geburtstag'=>$data['birthday'], 'beitritt'=>$data['joined_at'],
            'anschrift'=>$data['address_id'], 'handy'=>$data['mobile'],
            'email'=>$data['email'], 'mitgliedschaft'=>$data['membership'],
            'abrechnung'=>$data['billing'], 'kontoinhaber'=>'', 'kontonummer'=>0,
            'bankname'=>'', 'blz'=>0, 'dienste_lastyears'=>0,
            'parent1'=>$data['parent1'], 'parent2'=>$data['parent2'],
            'notizen'=>$data['notes'], 'username'=>$data['username'],
            'passwort'=>$passwordHash, 'rights'=>1,
            'lastbirthdaymail'=>0, 'lastjubileemail'=>0, 'lastlogintime'=>0,
            'lastloginip'=>'', 'currentlogintime'=>0, 'currentloginip'=>'', 'status'=>1,
        ];
        $columns = array_map(static fn(string $key): string => '`'.$key.'`', array_keys($values));
        $sql = 'INSERT INTO mitglieder ('.implode(',', $columns).') VALUES ('
            . implode(',', array_fill(0, count($values), '?')).')';
        try {
            $this->database->connection()->prepare($sql)->execute(array_values($values));
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new MemberValidationException(['number'=>'Mitgliedsnummer oder Benutzername ist bereits vergeben.']);
            }
            throw $exception;
        }
        return (int) $this->database->connection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        // Whitelist only editable profile fields. No login/password, roles,
        // status or bank credentials can be changed by this update endpoint.
        $map = [
            'number'=>'mitgliedsnummer', 'first_name'=>'vorname', 'last_name'=>'nachname',
            'birthday'=>'geburtstag', 'joined_at'=>'beitritt', 'email'=>'email',
            'mobile'=>'handy', 'gender'=>'geschlecht', 'membership'=>'mitgliedschaft',
            'billing'=>'abrechnung', 'address_id'=>'anschrift', 'parent1'=>'parent1',
            'parent2'=>'parent2', 'notes'=>'notizen',
        ];
        $sets = [];
        $values = [];
        foreach ($map as $formKey=>$column) {
            $sets[] = '`'.$column.'` = ?';
            $values[] = $data[$formKey];
        }
        $values[] = $id;
        try {
            $stmt = $this->database->connection()->prepare('UPDATE mitglieder SET '
                .implode(', ', $sets).' WHERE mitglieder_id = ? AND status = 1');
            $stmt->execute($values);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new MemberValidationException(['number'=>'Mitgliedsnummer ist bereits vergeben.']);
            }
            throw $exception;
        }
        // A valid no-op update is still a successful submission.
        return $this->byId($id)?->status === 1;
    }

    public function setStatus(int $id, int $status): bool
    {
        $stmt = $this->database->connection()->prepare(
            'UPDATE mitglieder SET status = ? WHERE mitglieder_id = ? AND status <> ? AND rights <> 5'
        );
        $stmt->execute([$status, $id, $status]);
        return $stmt->rowCount() === 1;
    }

    /** @param array<string,mixed> $r */
    private static function fromRow(array $r): Member
    {
        return new Member(
            (int)$r['mitglieder_id'], (string)$r['mitgliedsnummer'],
            (string)$r['vorname'], (string)$r['nachname'],
            (string)$r['geburtstag'], (string)$r['beitritt'],
            (string)$r['email'], (string)$r['handy'],
            (int)$r['geschlecht'], (int)$r['mitgliedschaft'], (int)$r['abrechnung'],
            (int)$r['anschrift'], (int)$r['parent1'], (int)$r['parent2'],
            (string)$r['notizen'], (string)$r['username'],
            (int)$r['rights'], (int)$r['status'],
        );
    }
}
