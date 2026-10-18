<?php
/** Persistence boundary for departments and their member assignments. */
declare(strict_types=1);
namespace Vereinssystem\Departments;
interface DepartmentRepository
{
    /** @return list<array<string,mixed>> */
    public function all(bool $includeInactive=false): array;
    /** @return array<string,mixed>|null */
    public function find(int $id): ?array;
    /** @return list<array<string,mixed>> */
    public function members(int $id): array;
    /** @param array<string,int|string> $values */
    public function save(array $values,?int $id): int;
    public function assign(int $department,int $member,int $active,string $since): void;
    public function unassign(int $department,int $member): bool;
    public function setStatus(int $id,int $status): bool;
}
