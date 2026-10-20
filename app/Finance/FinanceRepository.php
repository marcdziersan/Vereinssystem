<?php
/** Replaceable persistence contract. The service never builds SQL queries. */
declare(strict_types=1);
namespace Vereinssystem\Finance;
interface FinanceRepository
{
    /** @return array{claims:array,payments:array,totals:array,member:array} */
    public function statement(int $memberId,int $year): array;
    /** @return list<array<string,mixed>> */
    public function rates(): array;
    public function setRate(string $kind,int $departmentId,string $date,int $cents,int $actor): void;
    public function createClaim(int $member,string $period,string $kind,int $department,string $description,int $cents,string $due,int $actor): int;
    /** @return array{new:int,existing:int,cent:int} */
    public function generateMonth(string $period,int $actor): array;
    /** @return array{id:int,allocated:int,credit:int} */
    public function receivePayment(int $member,int $cents,string $date,string $channel,string $reference,string $note,int $actor,?int $preferred): array;
    public function reconcile(int $member): int;
    public function voidPayment(int $id,string $reason,int $actor): void;
    public function voidClaim(int $id,string $reason,int $actor): void;
}
