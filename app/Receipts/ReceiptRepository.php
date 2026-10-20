<?php
/** Cashbook attachment metadata persistence. Never exposes storage paths to web clients. */
declare(strict_types=1);
namespace Vereinssystem\Receipts;
use Vereinssystem\Models\CashReceipt;
interface ReceiptRepository
{
    /** @return array<int,list<CashReceipt>> */
    public function forEntries(array $entryIds):array;
    public function find(int $id):?CashReceipt;
    public function attach(int $entryId,string $original,string $mime,int $size,string $sha,string $stored,int $actor):int;
}
