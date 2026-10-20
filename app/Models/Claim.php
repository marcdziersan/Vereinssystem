<?php
/** An immutable contribution claim with a computed effective paid amount. */
declare(strict_types=1);
namespace Vereinssystem\Models;
final class Claim
{
    public function __construct(public readonly int $id,public readonly int $memberId,
        public readonly string $period,public readonly string $kind,public readonly string $description,
        public readonly int $cents,public readonly int $paidCents,public readonly string $dueOn,
        public readonly bool $cancelled) {}
    public function openCents(): int {return $this->cancelled?0:max(0,$this->cents-$this->paidCents);}
}
