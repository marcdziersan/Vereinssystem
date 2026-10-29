<?php
/**
 * Read-only preflight for existing and freshly installed MySQL schemas.
 * CREATE TABLE IF NOT EXISTS cannot detect divergent columns, engine or indexes.
 * Never repairs an existing table automatically: DBA must review differences.
 */
declare(strict_types=1);
namespace Vereinssystem\Install;

use PDO;
use RuntimeException;

final class SchemaInspector
{
    public const MIGRATIONS = [
        '001_members_auth.sql'=>1, '002_departments_places.sql'=>3,
        '003_events_tasks.sql'=>3, '004_news.sql'=>1,
        '005_finance.sql'=>4, '006_cash.sql'=>3,
        '007_cash_receipts.sql'=>1,
    ];
    public function __construct(private readonly string $root) {}

    /** @return array<string,array{columns:array<string,array{type:string,required:bool}>,indexes:array<string,bool>}> */
    public function expected(): array
    {
        $result=[];
        foreach (self::MIGRATIONS as $file=>$count) {
            $sql=@file_get_contents($this->root.'/database/migrations/'.$file);
            if (!is_string($sql)) throw new RuntimeException('Migrationsdatei fehlt: '.$file);
            preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`([^`]+)`\s*\((.*?)\)\s*ENGINE\s*=\s*InnoDB[^;]*;/si',$sql,$matches,PREG_SET_ORDER);
            if(count($matches)!==$count) throw new RuntimeException('Ungültige Tabellenzahl in '.$file);
            foreach($matches as $match) {
                $name=$match[1];
                if(isset($result[$name])) throw new RuntimeException('Doppelte Tabelle: '.$name);
                $columns=[];$indexes=[];
                foreach(preg_split('/\R/',$match[2]) as $line) {
                    if(preg_match('/^\s*`([^`]+)`\s+([a-z]+(?:\([^)]*\))?(?:\s+unsigned)?)/i',$line,$column)) {
                        $columns[$column[1]]=['type'=>self::normalizeType($column[2]),'required'=>(bool)preg_match('/\bNOT\s+NULL\b/i',$line)];
                    } elseif (preg_match('/^\s*PRIMARY\s+KEY\s*\(/i',$line)) {
                        $indexes['PRIMARY']=true;
                    } elseif(preg_match('/^\s*(UNIQUE\s+)?KEY\s+`([^`]+)`\s*\(/i',$line,$index)) {
                        $indexes[$index[2]]=trim(strtoupper($index[1]))==='UNIQUE';
                    }
                }
                if(!$columns||!isset($indexes['PRIMARY'])) throw new RuntimeException('Schema unvollständig: '.$name);
                $result[$name]=['columns'=>$columns,'indexes'=>$indexes];
            }
        }
        if(count($result)!==16)throw new RuntimeException('16 erwartete Tabellen nicht gefunden.');
        return $result;
    }

    private static function normalizeType(string $value): string
    {
        $value=strtolower(trim(preg_replace('/\s+/', ' ', $value)));
        // MySQL 8 removes integer display widths; they have no data-storage meaning.
        return preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\(\d+\)/','$1',$value);
    }

    /**
     * @return array{checked:int,missing:list<string>,errors:list<string>}
     * @param bool $requireAll Missing tables are acceptable BEFORE additive CREATEs.
     */
    public function inspect(PDO $pdo,bool $requireAll=false): array
    {
        $expected=$this->expected();
        $tables=$pdo->query('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchAll(PDO::FETCH_ASSOC);
        $existing=[];foreach($tables as $t)$existing[(string)$t['TABLE_NAME']]=strtoupper((string)($t['ENGINE']??''));
        $cols=$pdo->query('SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()')->fetchAll(PDO::FETCH_ASSOC);
        $actualColumns=[];
        foreach($cols as $col)$actualColumns[(string)$col['TABLE_NAME']][(string)$col['COLUMN_NAME']]=$col;
        $idx=$pdo->query('SELECT TABLE_NAME,INDEX_NAME,NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() GROUP BY TABLE_NAME,INDEX_NAME,NON_UNIQUE')->fetchAll(PDO::FETCH_ASSOC);
        $actualIndexes=[];
        foreach($idx as $i)$actualIndexes[(string)$i['TABLE_NAME']][(string)$i['INDEX_NAME']]=((int)$i['NON_UNIQUE']===0);
        $missing=[];$errors=[];$checked=0;
        foreach($expected as $name=>$spec) {
            if(!array_key_exists($name,$existing)) {$missing[]=$name;if($requireAll)$errors[]=$name.': Tabelle fehlt';continue;}
            ++$checked;
            if($existing[$name]!=='INNODB')$errors[]=$name.': InnoDB erwartet (gefunden '.$existing[$name].')';
            foreach($spec['columns'] as $field=>$expect){
                $actual=$actualColumns[$name][$field]??null;
                if(!$actual){$errors[]=$name.'.'.$field.': Spalte fehlt';continue;}
                $type=self::normalizeType((string)$actual['COLUMN_TYPE']);
                if($type!==$expect['type'])$errors[]=$name.'.'.$field.': Typ '.$type.' statt '.$expect['type'];
                if($expect['required'] && (string)$actual['IS_NULLABLE']!=='NO')$errors[]=$name.'.'.$field.': NOT NULL erwartet';
            }
            foreach($spec['indexes'] as $key=>$unique){
                if(!isset($actualIndexes[$name][$key]))$errors[]=$name.': Index '.$key.' fehlt';
                elseif($unique && $actualIndexes[$name][$key]!==true)$errors[]=$name.': Index '.$key.' muss UNIQUE sein';
            }
        }
        return ['checked'=>$checked,'missing'=>$missing,'errors'=>$errors];
    }

    /** @param list<string> $errors */
    public static function summary(array $errors): string
    {
        $display=array_slice($errors,0,6);
        return implode('; ',$display).(count($errors)>6?' (weitere '.(count($errors)-6).' Abweichungen)':'');
    }
}