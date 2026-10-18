<?php
/** All database access for login and role assignment; only selected member columns. */
declare(strict_types=1);

namespace Vereinssystem\Repositories;

use PDO;
use Vereinssystem\Core\Database;
use Vereinssystem\Auth\User;

final class PdoUserRepository implements UserRepository
{
    private const COLS = 'mitglieder_id, username, mitgliedsnummer, vorname, nachname, passwort, rights, status';

    public function __construct(private readonly Database $database) {}

    public function byIdentifier(string $identifier): ?User
    {
        // Two candidates avoid ambiguities between usernames and member numbers.
        $sql = 'SELECT ' . self::COLS . ' FROM mitglieder '
            . 'WHERE username = ? OR mitgliedsnummer = ? LIMIT 2';
        $stmt = $this->database->connection()->prepare($sql);
        $stmt->execute([$identifier, $identifier]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($rows) === 1 ? self::hydrate($rows[0]) : null;
    }

    public function byId(int $id): ?User
    {
        $stmt = $this->database->connection()->prepare('SELECT ' . self::COLS . ' FROM mitglieder WHERE mitglieder_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? self::hydrate($row) : null;
    }

    public function accessList(int $limit = 100): array
    {
        // SQL limit comes from a bounded server-side integer, not request SQL.
        $limit = max(1, min(200, $limit));
        $stmt = $this->database->connection()->prepare('SELECT ' . self::COLS . ' FROM mitglieder ORDER BY nachname, vorname, mitglieder_id LIMIT ' . $limit);
        $stmt->execute();
        return array_map(self::hydrate(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function setRights(int $id, int $level): bool
    {
        $stmt = $this->database->connection()->prepare('UPDATE mitglieder SET rights = ? WHERE mitglieder_id = ? AND status = 1');
        $stmt->execute([$level, $id]);
        return $stmt->rowCount() === 1;
    }

    public function replaceHash(int $id, string $newHash): void
    {
        $stmt = $this->database->connection()->prepare('UPDATE mitglieder SET passwort = ? WHERE mitglieder_id = ?');
        $stmt->execute([$newHash, $id]);
    }

    public function recordLogin(int $id, string $ip): void
    {
        $stmt = $this->database->connection()->prepare('UPDATE mitglieder SET '
            . 'lastlogintime = currentlogintime, lastloginip = currentloginip, '
            . 'currentlogintime = ?, currentloginip = ? WHERE mitglieder_id = ?');
        $stmt->execute([time(), substr($ip, 0, 45), $id]);
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): User
    {
        return new User((int)$row['mitglieder_id'], (string)$row['username'],
            (string)$row['mitgliedsnummer'], (string)$row['vorname'],
            (string)$row['nachname'], (string)$row['passwort'],
            (int)$row['rights'], (int)$row['status']);
    }
}
