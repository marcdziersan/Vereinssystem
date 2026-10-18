<?php
/** Testable port separating authentication logic from the physical PDO database. */
declare(strict_types=1);

namespace Vereinssystem\Repositories;

use Vereinssystem\Auth\User;

interface UserRepository
{
    public function byIdentifier(string $identifier): ?User;
    public function byId(int $id): ?User;
    /** @return list<User> */
    public function accessList(int $limit = 100): array;
    public function setRights(int $id, int $level): bool;
    public function replaceHash(int $id, string $newHash): void;
    public function recordLogin(int $id, string $ip): void;
}
