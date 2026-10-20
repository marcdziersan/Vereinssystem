<?php
/**
 * Read-only cash-book item. Transfers are a pair of entries with one shared key.
 * A cancelled entry remains on disk and is excluded from the active balance.
 */
declare(strict_types=1);
namespace Vereinssystem\Models;

final class CashEntry
{
    public const TYPES = ['einnahme','ausgabe','umbuchung_ein','umbuchung_aus'];
    public function __construct(
        public readonly int $id,
        public readonly int $accountId,
        public readonly string $accountName,
        public readonly string $type,
        public readonly int $amountCents,
        public readonly string $date,
        public readonly string $description,
        public readonly string $category,
        public readonly string $receiptNumber,
        public readonly ?int $paymentId,
        public readonly ?string $transferKey,
        public readonly bool $void,
    ) {}
}
