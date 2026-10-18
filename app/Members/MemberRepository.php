<?php
/** A testable contract; HTTP controllers never receive a PDO instance. */
declare(strict_types=1);

namespace Vereinssystem\Members;

interface MemberRepository
{
    public function byId(int $id): ?Member;

    /** @return array{items:list<Member>,total:int,page:int,pages:int} */
    public function search(string $term, int $page = 1, int $perPage = 20, string $status = 'active'): array;

    /** Counts are limited to active members; no sensitive data is returned. */
    public function statistics(): array;

    /** @param array<string,string|int> $data */
    public function create(array $data, string $passwordHash): int;

    /** @param array<string,string|int> $data */
    public function update(int $id, array $data): bool;

    public function setStatus(int $id, int $status): bool;
}
