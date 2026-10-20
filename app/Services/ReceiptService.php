<?php
/** Business validation, metadata persistence and private receipt retrieval. */
declare(strict_types=1);
namespace Vereinssystem\Services;
use Vereinssystem\Receipts\{ReceiptRepository,ReceiptStorage};
use Vereinssystem\Models\CashReceipt;
final class ReceiptService
{
    public function __construct(private readonly ReceiptRepository $repo,private readonly ReceiptStorage $storage) {}
    /** @return array<int,list<CashReceipt>> */
    public function forEntries(array $ids):array{return $this->repo->forEntries($ids);}
    public function upload(int $entryId,array $file,int $actor):int
    {
        if($entryId<1)throw new \DomainException('Buchungsnummer fehlt.');
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_string($file['tmp_name']??null)
            || !is_string($file['name']??null) || !is_int($file['size']??null))
            throw new \DomainException('Datei konnte nicht hochgeladen werden.');
        $m=$this->storage->inspect($file['tmp_name'],$file['name'],$file['size']);
        $stored=$this->storage->storeUploaded($file['tmp_name']);
        try{return $this->repo->attach($entryId,$m['name'],$m['mime'],$m['size'],$m['sha'],$stored,$actor);}
        catch(\Throwable $e){$this->storage->delete($stored);throw $e;}
    }
    /** @return array{receipt:CashReceipt,bytes:string} */
    public function download(int $id):array
    {
        if($id<1)throw new \DomainException('Belegnummer fehlt.');
        $receipt=$this->repo->find($id);
        if($receipt===null)throw new \DomainException('Beleg nicht gefunden.');
        return ['receipt'=>$receipt,'bytes'=>$this->storage->read($receipt->storedName,$receipt->checksum)];
    }
}
