<?php
/** Restrict the receipt vault to a real, private location under project storage/. */
declare(strict_types=1);
namespace Vereinssystem\Receipts;

final class StoragePolicy
{
    public static function verify(string $directory): string
    {
        $project=dirname(__DIR__,2);
        $storage=$project.'/storage';
        if(is_link($storage)||is_link($directory))throw new \RuntimeException('Symbolische Links für Belegspeicher sind nicht erlaubt.');
        $base=realpath($storage);
        $root=realpath($directory);
        if($base===false||$root===false||!is_dir($root)||!is_writable($root))
            throw new \RuntimeException('Privater Belegspeicher fehlt oder ist nicht beschreibbar.');
        if(!str_starts_with($root,$base.DIRECTORY_SEPARATOR))
            throw new \RuntimeException('Der Belegspeicher muss innerhalb von storage/ liegen.');
        $public=realpath($project.'/public');
        if($public!==false&&($root===$public||str_starts_with($root,$public.DIRECTORY_SEPARATOR)))
            throw new \RuntimeException('Belegspeicher im öffentlichen Webverzeichnis abgelehnt.');
        return $root;
    }
}