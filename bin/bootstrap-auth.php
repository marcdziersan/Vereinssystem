<?php
/**
 * CLI-only bootstrap for a fresh database. Creates the original-compatible
 * members table if absent and creates exactly one initial administrator.
 * Existing accounts and tables are NEVER overwritten.
 *
 *   php bin/bootstrap-auth.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require dirname(__DIR__).'/bootstrap.php';

use Vereinssystem\Core\Database;

$root=dirname(__DIR__);
$configFile=$root.'/config/database.local.php';
if (!is_file($configFile)) { fwrite(STDERR,"Bitte config/database.local.php einrichten.\n");exit(2); }
$c=require $configFile;
if (!is_array($c)) { fwrite(STDERR,"Ungültige Datenbankkonfiguration.\n");exit(2); }

/** Read one interactive value, never accepting secrets via command line args. */
function prompt(string $label): string
{
    fwrite(STDOUT,$label.': ');
    $value=fgets(STDIN);
    return trim($value===false?'':$value);
}

try {
    $pdo=(new Database($c))->connection();
    $ddl=file_get_contents($root.'/database/migrations/001_members_auth.sql');
    if (!is_string($ddl) || !preg_match('/CREATE TABLE IF NOT EXISTS `mitglieder`/',$ddl)) {
        throw new RuntimeException('Migration fehlt.');
    }
    // Single trusted schema statement from repository, not untrusted input.
    $pdo->exec($ddl);
    $count=(int)$pdo->query('SELECT COUNT(*) FROM mitglieder')->fetchColumn();
    if ($count!==0) {
        fwrite(STDOUT,"Die Datenbank enthält bereits {$count} Mitglied(er). Keine Änderung vorgenommen.\n");
        exit(0);
    }
    fwrite(STDOUT,"Neue, leere Vereinsdatenbank. Initiales Administratorkonto anlegen.\n");
    $first=prompt('Vorname');$last=prompt('Nachname');
    $login=prompt('Benutzername');$email=prompt('E-Mail-Adresse');
    $birth=prompt('Geburtsdatum (JJJJ-MM-TT)');
    $birthDate=DateTimeImmutable::createFromFormat('!Y-m-d',$birth);
    $password=prompt('Passwort (mind. 12 Zeichen, Eingabe auf der Konsole sichtbar)');
    if ($first===''||$last===''||!preg_match('/^[a-zA-Z0-9_.@-]{3,100}$/D',$login)
        || !filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12
        || !$birthDate || $birthDate->format('Y-m-d')!==$birth
        || $birthDate > new DateTimeImmutable('today')) {
        throw new RuntimeException('Eingaben ungültig; es wurde kein Konto erstellt.');
    }
    // Legacy-compatible NOT NULL fields. The forthcoming member module will
    // replace these defaults with proper validation and full member forms.
    $record=[
        'mitgliedsnummer'=>'ADMIN-MVC-0001','nachname'=>$last,'vorname'=>$first,
        'geschlecht'=>0,'geburtstag'=>$birth,'beitritt'=>date('Y-m-d'),
        'anschrift'=>0,'handy'=>'','email'=>$email,'mitgliedschaft'=>0,'abrechnung'=>0,
        'kontoinhaber'=>'','kontonummer'=>0,'bankname'=>'','blz'=>0,
        'dienste_lastyears'=>0,'parent1'=>0,'parent2'=>0,'notizen'=>'MVC-Bootstrap-Administrator',
        'username'=>$login,'passwort'=>password_hash($password,PASSWORD_DEFAULT),
        'rights'=>5,'lastbirthdaymail'=>0,'lastjubileemail'=>0,
        'lastlogintime'=>0,'lastloginip'=>'','currentlogintime'=>0,'currentloginip'=>'','status'=>1,
    ];
    unset($password);
    $fields=array_keys($record);
    $sql='INSERT INTO mitglieder ('.implode(',',array_map(static fn($s)=>'`'.$s.'`',$fields)).') VALUES ('
        .implode(',',array_fill(0,count($fields),'?')).')';
    $pdo->prepare($sql)->execute(array_values($record));
    fwrite(STDOUT,"Administrator angelegt. Jetzt mit dem neuen MVC-Login anmelden.\n");
} catch (Throwable $e) {
    error_log('[MVC bootstrap auth] '.$e::class.': '.$e->getMessage());
    fwrite(STDERR,"Fehler bei der Einrichtung. Details im PHP-Fehlerlog.\n");
    exit(1);
}
