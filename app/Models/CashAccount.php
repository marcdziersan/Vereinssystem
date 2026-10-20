<?php
/**
 * Immutable projection of a club cash/bank/PayPal account.
 * All monetary data is in integer cents; a closing balance is a derived value.
 */
declare(strict_types=1);
namespace Vereinssystem\Models;

final class CashAccount
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
        public readonly int $openingCents,
        public readonly string $openingDate,
        public readonly bool $active,
        public readonly int $balanceCents,
    ) {}
}
