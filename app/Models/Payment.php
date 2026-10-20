<?php
/** A payment is never physically deleted. Reversals preserve the audit trail. */
declare(strict_types=1);
namespace Vereinssystem\Models;
final class Payment
{
    public function __construct(public readonly int $id,public readonly int $memberId,
        public readonly int $cents,public readonly int $allocatedCents,public readonly string $paidOn,
        public readonly string $channel,public readonly string $reference,public readonly bool $cancelled) {}
    public function creditCents(): int {return $this->cancelled?0:max(0,$this->cents-$this->allocatedCents);}
}
