<?php
/**
 * Immutable annual cash-report projection.
 *
 * The repository supplies per-account reconciled integer-cent totals. This
 * model carries the report to HTML, CSV and PDF without repeating SQL.
 */
declare(strict_types=1);
namespace Vereinssystem\Models;

final class CashReport
{
    /**
     * @param list<array<string,mixed>> $accounts
     * @param list<array<string,mixed>> $entries
     * @param array<string,int> $totals
     */
    public function __construct(
        public readonly int $year,
        public readonly ?int $accountId,
        public readonly array $accounts,
        public readonly array $entries,
        public readonly array $totals,
    ) {}
}
