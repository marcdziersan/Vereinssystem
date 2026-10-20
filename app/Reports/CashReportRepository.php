<?php
/**
 * Read-only report persistence contract. No artificial 300-row journal limit:
 * reports and exports must cover the complete selected financial year.
 */
declare(strict_types=1);
namespace Vereinssystem\Reports;
interface CashReportRepository
{
    /**
     * A single read-consistent year snapshot. Do not combine two independent
     * reads while concurrent cash/finance transactions might be committing.
     * @return array{accounts:list<array<string,mixed>>,entries:list<array<string,mixed>>}
     */
    public function snapshot(int $year, ?int $accountId):array;
}
