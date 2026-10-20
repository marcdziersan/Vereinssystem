<?php
/** One-time installer. Never overwrites a pre-existing configuration or members. */
declare(strict_types=1);
namespace Vereinssystem\Install;

use DateTimeImmutable;
use PDO;
use Vereinssystem\Core\Database;

final class InstallationService
{
    public function __construct(private readonly string $root) {}
    public function installed(): bool {return is_file($this->root.'/config/database.local.php');}

    /** @param array<string,string> $fields */
    public function install(array $fields): string
    {
        if ($this->installed()) throw new \DomainException('Die Anwendung wurde bereits eingerichtet.');
        $host=trim($fields['host']??'localhost');$database=trim($fields['database']??'');
        $username=trim($fields['username']??'');$password=$fields['db_password']??'';
        $port=filter_var($fields['port']??'3306',FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
        if(!preg_match('/^[A-Za-z0-9.:-]{1,200}$/D',$host)||!preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$database)||$username===''||$port===false)
            throw new \DomainException('Datenbankverbindung: Bitte Angaben prüfen.');
        $first=trim($fields['first']??'');$last=trim($fields['last']??'');
        $login=trim($fields['login']??'');$email=trim($fields['email']??'');
        $birth=trim($fields['birthday']??'');$adminPassword=$fields['admin_password']??'';
        $birthDate=DateTimeImmutable::createFromFormat('!Y-m-d',$birth);
        if($first===''||$last===''||strlen($first)>100||strlen($last)>100||
            !preg_match('/^[A-Za-z0-9_.@-]{3,100}$/D',$login)||!filter_var($email,FILTER_VALIDATE_EMAIL)||
            !$birthDate||$birthDate->format('Y-m-d')!==$birth||$birthDate>new DateTimeImmutable('today')||
            strlen($adminPassword)<12||strlen($adminPassword)>4096||!hash_equals($adminPassword,$fields['confirm_password']??''))
            throw new \DomainException('Administrator: Bitte gültige Angaben und zweimal dasselbe Passwort (mind. 12 Zeichen) eingeben.');
        if(!extension_loaded('pdo_mysql'))throw new \DomainException('Die PHP-Erweiterung pdo_mysql ist nicht aktiviert.');

        $config=['host'=>$host,'port'=>$port,'database'=>$database,'username'=>$username,'password'=>$password];
        $pdo=(new Database($config))->connection();
        // Run only known, bundled CREATE TABLE IF NOT EXISTS statements, without accepting SQL from HTTP.
        foreach(['001_members_auth.sql','002_departments_places.sql','003_events_tasks.sql','004_news.sql','005_finance.sql','006_cash.sql','007_cash_receipts.sql'] as $file) {
            $schema=file_get_contents($this->root.'/database/migrations/'.$file);
            if (!is_string($schema)) throw new \RuntimeException('Schema-Datei nicht gefunden.');
            preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=InnoDB[^;]*;/si',$schema,$statements);
            if(count($statements[0])!==match($file){'001_members_auth.sql'=>1,'002_departments_places.sql'=>3,'003_events_tasks.sql'=>3,'004_news.sql'=>1,'005_finance.sql'=>4,'006_cash.sql'=>3,'007_cash_receipts.sql'=>1})throw new \RuntimeException('Installationsschema unvollständig.');
            foreach($statements[0] as $sql)$pdo->exec($sql);
        }
        $count=(int)$pdo->query('SELECT COUNT(*) FROM mitglieder')->fetchColumn();
        if($count===0){
            // No second administrator is created on existing VereinsSystem data.
            $record=[
                'mitgliedsnummer'=>'ADMIN-MVC-0001','nachname'=>$last,'vorname'=>$first,
                'geschlecht'=>0,'geburtstag'=>$birth,'beitritt'=>date('Y-m-d'),
                'anschrift'=>0,'handy'=>'','email'=>$email,'mitgliedschaft'=>0,'abrechnung'=>0,
                'kontoinhaber'=>'','kontonummer'=>0,'bankname'=>'','blz'=>0,
                'dienste_lastyears'=>0,'parent1'=>0,'parent2'=>0,'notizen'=>'MVC-Installationsadministrator',
                'username'=>$login,'passwort'=>password_hash($adminPassword,PASSWORD_DEFAULT),
                'rights'=>5,'lastbirthdaymail'=>0,'lastjubileemail'=>0,'lastlogintime'=>0,
                'lastloginip'=>'','currentlogintime'=>0,'currentloginip'=>'','status'=>1,
            ];
            $pdo->beginTransaction();
            try{
                $columns=array_keys($record);
                $sql='INSERT INTO mitglieder ('.implode(',',array_map(static fn($x)=>'`'.$x.'`',$columns)).') VALUES ('.implode(',',array_fill(0,count($columns),'?')).')';
                $pdo->prepare($sql)->execute(array_values($record));$pdo->commit();
            }catch(\Throwable $e){$pdo->rollBack();throw $e;}
        }
        // Do not save credentials unless the tables and admin initialization succeeded.
        $file=$this->root.'/config/database.local.php';
        $handle=@fopen($file,'x'); // exclusive creation avoids simultaneous installer overwrites
        if($handle===false)throw new \RuntimeException('Konfigurationsdatei konnte nicht angelegt werden. Zugriffsrechte prüfen.');
        try{
            @chmod($file,0600);
            $content="<?php\n/** Lokale Datenbankkonfiguration. Niemals ins Repository einchecken. */\nreturn ".var_export($config,true).";\n";
            if(fwrite($handle,$content)!==strlen($content))throw new \RuntimeException('Konfigurationsdatei nicht vollständig geschrieben.');
            fflush($handle);
        }catch(\Throwable $e){fclose($handle);@unlink($file);throw $e;}
        fclose($handle);
        return $count===0?'Neuinstallation erfolgreich. Administrator wurde angelegt.':'Bestehende Mitglieder erhalten. Fehlende Tabellen wurden ergänzt.';
    }
}
