<?php
/** Immutable financial-year projection, amounts always stored in integer cents. */
declare(strict_types=1);
namespace Vereinssystem\Models;
final class CashAnnualReport
{
    /**
     * @param list<array<string,mixed>> $accounts
     * @param list<array<string,mixed>> $entries
     * @param list<array<string,int>> $monthly
     * @param list<array{category:string,income:int,expense:int}> $categories
     * @param array<string,int> $totals
     */
    public function __construct(
        public readonly int $year,
        public readonly ?int $accountId,
        public readonly array $accounts,
        public readonly array $entries,
        public readonly array $monthly,
        public readonly array $categories,
        public readonly array $totals,
    ) {}
}
