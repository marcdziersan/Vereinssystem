<?php
/**
 * Private binary storage. File names are random identifiers without extensions.
 * Neither a client-supplied path nor a MIME header is trusted. Apache denies
 * /storage directly; only the permission-checked controller can read bytes.
 */
declare(strict_types=1);
namespace Vereinssystem\Receipts;
final class ReceiptStorage
{
    public const MAX_BYTES=5_242_880; // 5 MiB per upload
    private const MIMES=['application/pdf','image/jpeg','image/png','image/webp'];
    public function __construct(private readonly string $directory) {}

    /** @return array{name:string,mime:string,size:int,sha:string} */
    public function inspect(string $tmp,string $original,int $size):array
    {
        if($size<1||$size>self::MAX_BYTES||!is_file($tmp)||!is_readable($tmp) || filesize($tmp)!==$size)
            throw new \DomainException('Datei fehlt oder überschreitet 5 MiB.');
        $name=trim(str_replace('\\','/', $original));
        $name=basename($name);
        if($name===''||strlen($name)>180||preg_match('/[\x00-\x1F\x7F]/',$name))throw new \DomainException('Ungültiger Dateiname.');
        if(!class_exists(\finfo::class))throw new \RuntimeException('PHP fileinfo ist erforderlich.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if(!is_string($mime)||!in_array($mime,self::MIMES,true))throw new \DomainException('Nur PDF, JPEG, PNG und WebP erlaubt.');
        if($mime==='application/pdf'){
            $f=fopen($tmp,'rb');$header=$f===false?'':fread($f,5);if($f!==false)fclose($f);
            if($header!=='%PDF-')throw new \DomainException('Ungültige PDF-Datei.');
        }elseif(@getimagesize($tmp)===false)throw new \DomainException('Ungültige Bilddatei.');
        $checksum=hash_file('sha256',$tmp);
        if($checksum===false)throw new \RuntimeException('Prüfsumme fehlgeschlagen.');
        return ['name'=>$name,'mime'=>$mime,'size'=>$size,'sha'=>$checksum];
    }

    /** Browser-only upload: rejects paths fabricated in POST payloads. */
    public function storeUploaded(string $tmp):string
    {
        if(!is_uploaded_file($tmp))throw new \DomainException('Ungültiger Datei-Upload.');
        if(!is_dir($this->directory)&&!@mkdir($this->directory,0700,true)&&!is_dir($this->directory))
            throw new \RuntimeException('Privater Belegordner nicht beschreibbar.');
        $name=bin2hex(random_bytes(16)).'.bin';
        $dest=$this->directory.'/'.$name;
        if(!move_uploaded_file($tmp,$dest))throw new \RuntimeException('Upload konnte nicht gespeichert werden.');
        @chmod($dest,0600);
        return $name;
    }
    public function delete(string $name):void
    {
        if(preg_match('/^[a-f0-9]{32}\.bin$/D',$name))@unlink($this->directory.'/'.$name);
    }
    public function read(string $name,string $checksum):string
    {
        if(!preg_match('/^[a-f0-9]{32}\.bin$/D',$name))throw new \DomainException('Beleg nicht gefunden.');
        $path=$this->directory.'/'.$name;
        if(!is_file($path)||filesize($path)>self::MAX_BYTES)throw new \RuntimeException('Beleg nicht lesbar.');
        $bytes=@file_get_contents($path);
        if(!is_string($bytes)||strlen($bytes)>self::MAX_BYTES||hash('sha256',$bytes)!==$checksum)
            throw new \RuntimeException('Datei fehlt oder Prüfsumme stimmt nicht.');
        return $bytes;
    }
}
