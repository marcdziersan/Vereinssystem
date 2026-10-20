<?php
/** The annual report is deliberately unpaginated; unlike the 300-row journal it covers every entry. */
declare(strict_types=1);
namespace Vereinssystem\Reports;
use Vereinssystem\Models\CashReport;
interface ReportRepository
{
    public function annual(int $year,?int $accountId=null):CashReport;
}
