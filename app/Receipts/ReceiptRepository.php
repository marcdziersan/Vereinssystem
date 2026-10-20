<?php
/** Metadata persistence contract for private, permission-checked cash receipts. */
declare(strict_types=1);
namespace Vereinssystem\Receipts;
use Vereinssystem\Models\Receipt;
interface ReceiptRepository
{
    /** @param list<int> $entryIds @return array<int,list<Receipt>> */
    public function forEntries(array $entryIds):array;
    public function byId(int $id):?Receipt;
    public function add(int $entryId,string $original,string $stored,string $mime,int $size,string $checksum,int $actor):int;
}
