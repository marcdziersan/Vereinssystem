<?php
/** PDO account changes compatible with the historical mitglieder schema. */
declare(strict_types=1);

namespace Vereinssystem\Accounts;

use PDO;
use PDOException;
use Vereinssystem\Core\Database;

final class PdoAccountRepository implements AccountRepository
{
    public function __construct(private readonly Database $database) {}

    public function profile(int $memberId): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT username,email,handy FROM mitglieder WHERE mitglieder_id = ? AND status = 1 LIMIT 1'
        );
        $statement->execute([$memberId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? [
            'username' => (string)$row['username'],
            'email' => (string)$row['email'],
            'mobile' => (string)$row['handy'],
        ] : null;
    }

    public function updateProfile(int $memberId, string $expectedHash, string $username, string $email, string $mobile): bool
    {
        try {
            $statement = $this->database->connection()->prepare(
                'UPDATE mitglieder SET username = ?, email = ?, handy = ? '
                . 'WHERE mitglieder_id = ? AND status = 1 AND passwort = ?'
            );
            $statement->execute([$username, $email, $mobile, $memberId, $expectedHash]);
        } catch (PDOException $exception) {
            if ((string)$exception->getCode() === '23000') {
                throw new \DomainException('Dieser Benutzername ist bereits vergeben.', 0, $exception);
            }
            throw $exception;
        }
        // MySQL returns 0 for an unchanged row even though the operation is valid.
        if ($statement->rowCount() === 1) return true;
        // Check the old hash still matches before accepting an unchanged-row update.
        $check = $this->database->connection()->prepare(
            'SELECT COUNT(*) FROM mitglieder WHERE mitglieder_id = ? AND status = 1 AND passwort = ?'
        );
        $check->execute([$memberId, $expectedHash]);
        if ((int)$check->fetchColumn() !== 1) return false;
        $current = $this->profile($memberId);
        return $current !== null && $current === ['username'=>$username,'email'=>$email,'mobile'=>$mobile];
    }

    public function changePassword(int $memberId, string $expectedHash, string $newHash): bool
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE mitglieder SET passwort = ? WHERE mitglieder_id = ? AND status = 1 AND passwort = ?'
        );
        $statement->execute([$newHash, $memberId, $expectedHash]);
        return $statement->rowCount() === 1;
    }
}
