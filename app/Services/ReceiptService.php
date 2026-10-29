<?php
/**
 * Private file vault for cashbook receipts.
 *
 * Only real PHP uploads are accepted in production. MIME is determined from
 * file contents, never the client-supplied filename or Content-Type header.
 * Storage names are random 64-character hex strings without extensions.
 * A failed database write removes the already moved file.
 */
declare(strict_types=1);
namespace Vereinssystem\Services;

use Vereinssystem\Models\Receipt;
use Vereinssystem\Receipts\{ReceiptRepository,StoragePolicy};

final class ReceiptService
{
    public const MAX_BYTES=8388608;
    private const ALLOWED=['application/pdf','image/jpeg','image/png','image/webp'];

    public function __construct(private readonly ReceiptRepository $repository,private readonly string $directory) {}

    /** @param array<string,mixed>|null $upload */
    public function upload(int $entryId,?array $upload,int $actor):int
    {
        if($entryId<1||$actor<1)throw new \DomainException('Ungültige Buchung oder Benutzer.');
        if($upload===null||($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)
            throw new \DomainException('Bitte eine gültige Datei hochladen.');
        $tmp=$upload['tmp_name']??null;$size=$upload['size']??null;
        if(!is_string($tmp)||!is_int($size)||$size<1||$size>self::MAX_BYTES||!is_uploaded_file($tmp))
            throw new \DomainException('Datei fehlt, ist nicht authentisch oder überschreitet 8 MB.');
        $realSize=filesize($tmp);
        if($realSize===false||$realSize!==$size)throw new \DomainException('Die Dateigröße stimmt nicht überein.');
        $original=self::safeFilename((string)($upload['name']??''));
        $mime=self::detectMime($tmp);
        $stored=bin2hex(random_bytes(32));
        $dir=$this->protectedDirectory();
        $target=$dir.'/'.$stored;
        if(!move_uploaded_file($tmp,$target))throw new \RuntimeException('Datei konnte nicht gespeichert werden.');
        @chmod($target,0600);
        try{
            $checksum=hash_file('sha256',$target);
            if($checksum===false)throw new \RuntimeException('Datei konnte nicht geprüft werden.');
            return $this->repository->add($entryId,$original,$stored,$mime,$size,$checksum,$actor);
        }catch(\Throwable $e){@unlink($target);throw $e;}
    }

    /** @return array{content:string,filename:string,mime:string} */
    public function download(int $id):array
    {
        if($id<1)throw new \DomainException('Ungültiger Beleg.');
        $r=$this->repository->byId($id);
        if(!$r)throw new \DomainException('Beleg nicht gefunden.');
        if(!preg_match('/^[a-f0-9]{64}$/D',$r->storageName))throw new \RuntimeException('Ungültige Belegreferenz.');
        $root=StoragePolicy::verify($this->directory);
        $candidate=$root.'/'.$r->storageName;
        $file=realpath($candidate);
        if($file===false||dirname($file)!==$root||!is_file($file)||is_link($candidate))
            throw new \DomainException('Datei nicht mehr vorhanden.');
        $length=filesize($file);
        if($length===false||$length!==$r->size||$length>self::MAX_BYTES||
            !hash_equals($r->checksum,(string)hash_file('sha256',$file)))
            throw new \RuntimeException('Belegintegrität fehlgeschlagen.');
        $content=file_get_contents($file);
        if($content===false)throw new \RuntimeException('Datei kann nicht gelesen werden.');
        return ['content'=>$content,'filename'=>$r->originalName,'mime'=>$r->mime];
    }

    /** Validate filename independently of a browser; file paths never come from this value. */
    public static function safeFilename(string $name):string
    {
        $name=basename(str_replace('\\','/',$name));
        $name=trim(preg_replace('/[\x00-\x1F\x7F]/u','',$name)??'');
        if($name===''||$name==='.'||$name==='..')throw new \DomainException('Dateiname ungültig.');
        // The DB schema from master limits the original name to 180 bytes.
        if(strlen($name)>180)throw new \DomainException('Dateiname zu lang.');
        return $name;
    }

    public static function detectMime(string $filename):string
    {
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($filename);
        if(!is_string($mime)||!in_array($mime,self::ALLOWED,true))
            throw new \DomainException('Nur PDF, JPEG, PNG oder WebP erlaubt.');
        return $mime;
    }

    private function protectedDirectory():string
    {
        if(!is_dir($this->directory)&&!mkdir($this->directory,0700,true)&&!is_dir($this->directory))
            throw new \RuntimeException('Belegverzeichnis nicht beschreibbar.');
        // Root .htaccess already protects /storage; enforce protection inside too.
        $root=StoragePolicy::verify($this->directory);
        if(!is_file($root.'/.htaccess')){
            $ok=@file_put_contents($root.'/.htaccess',"Options -Indexes\nRequire all denied\n",LOCK_EX);
            if($ok===false)throw new \RuntimeException('Belegschutz nicht verfügbar.');
        }
        return $root;
    }
}
