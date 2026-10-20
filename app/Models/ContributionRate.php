<?php
/** Immutable representation of a historical contribution tariff. */
declare(strict_types=1);
namespace Vereinssystem\Models;
final class ContributionRate
{
    public function __construct(public readonly int $id,public readonly string $kind,
        public readonly int $departmentId,public readonly string $effectiveFrom,public readonly int $cents) {}
}
