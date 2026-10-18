<?php
/** Database boundary for address/location records. No HTML or session code. */
declare(strict_types=1);
namespace Vereinssystem\Places;

interface PlaceRepository
{
    /** @return list<array<string,mixed>> */
    public function all(bool $includeInactive = false): array;
    /** @return array<string,mixed>|null */
    public function find(int $id): ?array;
    /** @param array<string,int|string> $values */
    public function save(array $values, ?int $id, int $actor): int;
    public function setStatus(int $id, int $status): bool;
}
