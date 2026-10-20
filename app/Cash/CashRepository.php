<?php
/** Cash persistence contract. Test implementations can exercise the service without MySQL. */
declare(strict_types=1);
namespace Vereinssystem\Cash;

interface CashRepository
{
    /** @return list<\Vereinssystem\Models\CashAccount> */
    public function accounts(bool $includeInactive=true): array;
    /** @return list<\Vereinssystem\Models\CashEntry> */
    public function entries(int $year, ?int $accountId=null): array;
    public function createAccount(string $name, string $type, int $opening, string $date, int $actor): int;
    public function setAccount(int $id, string $name, string $type, bool $active, int $actor): void;
    public function book(int $account, string $type, int $cents, string $date, string $category, string $description, string $receipt, int $actor): int;
    /** @return array{0:int,1:int} IDs of debit and credit entries */
    public function transfer(int $from, int $to, int $cents, string $date, string $description, int $actor): array;
    public function voidEntry(int $id, string $reason, int $actor): void;
    /** @return array{id:int,allocated:int,credit:int,cash_id:int} */
    public function contribution(int $account, int $member, int $cents, string $date, string $channel, string $reference, string $note, int $actor, ?int $preferred): array;
    /** Atomic void of payment and its linked cash entry; also supports older unlinked payments. */
    public function voidContribution(int $paymentId, string $reason, int $actor): void;
}
