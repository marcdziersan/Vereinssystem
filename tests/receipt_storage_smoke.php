<?php
/** Confirm that the receipt vault never accepts a symlink or the webroot. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Receipts\StoragePolicy;
$root=dirname(__DIR__);
$base=$root.'/storage';
$directory=$base.'/receipts';
if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory))throw new RuntimeException('Testverzeichnis fehlt');
if(!is_file($directory.'/.htaccess'))file_put_contents($directory.'/.htaccess',"Options -Indexes\nRequire all denied\n");
$valid=StoragePolicy::verify($directory);
if($valid!==realpath($directory))throw new RuntimeException('Valid private path rejected');
try{StoragePolicy::verify($root.'/public');throw new RuntimeException('Public directory accepted');}
catch(RuntimeException $e){if($e->getMessage()==='Public directory accepted')throw $e;}
if(function_exists('symlink') && DIRECTORY_SEPARATOR==='/'){
    $link=$base.'/receipt-link-'.getmypid();
    if(@symlink($directory,$link)) {
        try{StoragePolicy::verify($link);throw new RuntimeException('Symlink accepted');}
        catch(RuntimeException $e){if($e->getMessage()==='Symlink accepted')throw $e;}
        finally{@unlink($link);}
    }
}
echo "PASS: Receipt storage directory policy.\n";
