<?php
/**
 * Offline PHP release preflight, no Composer, PHPUnit or network dependencies.
 * php bin/release-audit.php [--manifest=relative-or-absolute-path]
 * The manifest excludes secrets, runtime data and .git by design.
 */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Nur CLI.');}
$root=dirname(__DIR__);
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
$files=[];
foreach($iterator as $entry){
    if(!$entry->isFile()||$entry->isLink())continue;
    $relative=str_replace('\\','/',substr($entry->getPathname(),strlen($root)+1));
    if(preg_match('~^(?:\.git/|storage/|config/.*\.local\.php$|(?:backup|cache|tmp)/|release-manifest\.json$)~i',$relative))continue;
    $files[$relative]=$entry->getPathname();
}
ksort($files,SORT_STRING);
$phpFiles=array_filter($files,static fn($path)=>str_ends_with(strtolower($path),'.php'),ARRAY_FILTER_USE_KEY);
$failures=[];
foreach($phpFiles as $relative=>$absolute){
    $process=@proc_open([PHP_BINARY,'-l',$absolute], [1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
    if(!is_resource($process)){ $failures[]=$relative.': PHP lint konnte nicht gestartet werden';continue;}
    $output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);fclose($pipes[2]);
    if(proc_close($process)!==0)$failures[]=$relative.': '.trim($output);
}
echo 'Quellcodedateien: '.count($files).'; PHP-Syntaxprüfungen: '.count($phpFiles).PHP_EOL;
if($failures){foreach($failures as $failure)fwrite(STDERR,'FEHLER: '.$failure.PHP_EOL);exit(1);}
// A release inventory is reproducible for the same tracked source tree; generated
// secrets and cache files cannot leak into it, since they are explicitly excluded.
foreach($argv as $argument){
    if(!str_starts_with($argument,'--manifest='))continue;
    $target=substr($argument,11);
    if($target===''||is_dir($target))throw new RuntimeException('Manifest-Dateipfad fehlt.');
    $manifest=['schema'=>1,'generated_at'=>gmdate('c'),'files'=>[]];
    foreach($files as $relative=>$absolute)$manifest['files'][$relative]=hash_file('sha256',$absolute);
    $written=file_put_contents($target,json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);
    if($written===false)throw new RuntimeException('Manifest konnte nicht geschrieben werden.');
    echo 'SHA-256-Manifest erstellt: '.$target.PHP_EOL;
}
echo "PASS: Syntaxprüfung. Fachprüfungen und WAMP-/MySQL-Abnahme separat ausführen.\n";
