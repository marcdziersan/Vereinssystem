<?php
/** Interface for revisioned public club configuration and private audit history. */
declare(strict_types=1);
namespace Vereinssystem\Settings;
interface SettingsRepository
{
    /** @return array{revision:int,values:array<string,string>} */
    public function load():array;
    /** Atomically replace validated non-secret settings and append an audit record. */
    public function save(array $values,int $expectedRevision,int $actorId):int;
    /** @return list<array{id:int,actor_id:int,revision:int,changes:array<string,array{before:string,after:string}>,created_at:string}> */
    public function history(int $limit=30,int $offset=0):array;
}
