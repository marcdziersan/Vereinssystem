<?php
declare(strict_types=1);
/** Geführte Neuinstallation / Anbindung einer bestehenden Vereinsdatenbank.
 * Konfiguration erst bei vollständig erfolgreicher Datenbankinitialisierung schreiben.
 */
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
error_reporting(E_ALL);
ini_set('display_errors','0');
$configPath = __DIR__.'/inc/config.php';
$installed = is_file($configPath);
$errors = [];
$success = false;
$existing = false;
$createdAdmin = false;
if (!$installed && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form = [
        'host'=>trim((string)($_POST['host'] ?? 'localhost')),
        'port'=> (int)($_POST['port'] ?? 3306),
        'database'=>trim((string)($_POST['database'] ?? '')),
        'username'=>trim((string)($_POST['username'] ?? '')),
        'password'=>(string)($_POST['password'] ?? ''),
        'club'=>trim((string)($_POST['club'] ?? 'Mein Verein')),
        'first'=>trim((string)($_POST['first'] ?? '')),
        'last'=>trim((string)($_POST['last'] ?? '')),
        'adminuser'=>trim((string)($_POST['adminuser'] ?? 'admin')),
        'adminemail'=>trim((string)($_POST['adminemail'] ?? '')),
        'adminbirth'=>trim((string)($_POST['adminbirth'] ?? '')),
        'adminpass'=>(string)($_POST['adminpass'] ?? ''),
    ];
    if (PHP_VERSION_ID < 80000) $errors[] = 'PHP 8.0 oder neuer ist erforderlich.';
    if (!extension_loaded('pdo_mysql')) $errors[] = 'PHP-Erweiterung pdo_mysql ist nicht aktiviert.';
    if (!preg_match('/^[a-zA-Z0-9_.-]{1,128}$/D', $form['host'])) $errors[] = 'Ungültiger Datenbankhost.';
    if ($form['port'] < 1 || $form['port'] > 65535) $errors[] = 'Ungültiger Port.';
    if (!preg_match('/^[a-zA-Z0-9_\-]{1,64}$/D', $form['database'])) $errors[] = 'Bitte gültigen Datenbanknamen eintragen.';
    if ($form['username'] === '') $errors[] = 'Datenbankbenutzer fehlt.';
    if ($form['club'] === '') $errors[] = 'Vereinsname fehlt.';
    if ($form['first'] === '' || $form['last'] === '') $errors[] = 'Vor- und Nachname des Admins fehlen.';
    if (!preg_match('/^[a-zA-Z0-9_.@-]{3,100}$/D', $form['adminuser'])) $errors[] = 'Admin-Nutzername muss 3–100 Zeichen haben (Buchstaben, Zahlen, ._-@).';
    if (!filter_var($form['adminemail'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Admin-E-Mail-Adresse ist ungültig.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $form['adminbirth']) || strtotime($form['adminbirth']) < strtotime('1900-01-01') || strtotime($form['adminbirth']) > time()) $errors[] = 'Bitte das tatsächliche Geburtsdatum des Administrators angeben.';
    if (strlen($form['adminpass']) < 12) $errors[] = 'Admin-Passwort: mindestens 12 Zeichen.';
    if (!hash_equals($form['adminpass'], (string)($_POST['adminpass2'] ?? ''))) $errors[] = 'Die Administrator-Passwörter stimmen nicht überein.';
    if (!is_writable(__DIR__.'/inc')) $errors[] = 'Ordner inc/ ist nicht beschreibbar.';
    if (!is_readable(__DIR__.'/sql/schema.sql')) $errors[] = 'sql/schema.sql fehlt.';
    if (!$errors) {
        try {
            $dsn = "mysql:host={$form['host']};port={$form['port']};dbname={$form['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $form['username'], $form['password'], [
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false,
                PDO::ATTR_TIMEOUT=>10,
            ]);
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $existing = in_array('mitglieder', $tables, true);
            if ($existing && !in_array('einstellungen', $tables, true)) {
                throw new RuntimeException('Die Datenbank enthält eine unvollständige Altinstallation (Tabelle einstellungen fehlt).');
            }
            $schema = file_get_contents(__DIR__.'/sql/schema.sql');
            if (!preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=.*?;/si', $schema, $statements) || count($statements[0]) < 8) {
                throw new RuntimeException('Das Installationsschema konnte nicht vollständig gelesen werden.');
            }
            foreach ($statements[0] as $createSql) $pdo->exec($createSql);
            // DDL ist in MySQL nicht vollständig transaktional; Seed-Daten dagegen schon.
            $pdo->beginTransaction();
            $defaults = [
                'vereinsname'=>$form['club'], 'vereinsname_lang'=>$form['club'],
                'vereinsanschrift'=>'0', 'beitrag_kinder'=>'0', 'beitrag_erwachsener'=>'0',
                'usecaptchalogin'=>'0', 'annualwork'=>'0', 'cakeworkcount'=>'0',
                'webmastermail'=>$form['adminemail'], 'leitermail'=>$form['adminemail'],
                'lastcronrun'=>'0', 'lastcronoutput'=>'', 'last_diensteabrechnung'=>'0', 'version'=>'2.1.0',
            ];
            $insertSetting = $pdo->prepare('INSERT INTO `einstellungen` (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `name`=`name`');
            foreach ($defaults as $key=>$value) $insertSetting->execute([$key, $value]);
            $n = (int)$pdo->query('SELECT COUNT(*) FROM `mitglieder`')->fetchColumn();
            if ($n === 0) {
                $admin = [
                    'mitgliedsnummer'=>'ADMIN-0001', 'nachname'=>$form['last'], 'vorname'=>$form['first'],
                    'geschlecht'=>0, 'geburtstag'=>$form['adminbirth'], 'beitritt'=>date('Y-m-d'),
                    'anschrift'=>0, 'handy'=>'', 'email'=>$form['adminemail'],
                    'mitgliedschaft'=>0, 'abrechnung'=>1, 'kontoinhaber'=>'', 'kontonummer'=>0,
                    'bankname'=>'', 'blz'=>0, 'dienste_lastyears'=>0, 'parent1'=>0, 'parent2'=>0,
                    'notizen'=>'Initiales Administratorkonto', 'username'=>$form['adminuser'],
                    'passwort'=>password_hash($form['adminpass'], PASSWORD_DEFAULT), 'rights'=>5,
                    'lastbirthdaymail'=>time(), 'lastjubileemail'=>time(), 'lastlogintime'=>0, 'lastloginip'=>'',
                    'currentlogintime'=>0, 'currentloginip'=>'', 'status'=>1,
                ];
                $columns = array_map(static fn(string $c): string => '`'.$c.'`',array_keys($admin));
                $placeholders = implode(',',array_fill(0,count($admin),'?'));
                $pdo->prepare('INSERT INTO `mitglieder` ('.implode(',',$columns).') VALUES ('.$placeholders.')')->execute(array_values($admin));
                $createdAdmin = true;
            }
            $pdo->commit();
            // Nur PHP-Datei (kein Klartext-/JSON-Download), mit Zugriffsschutz für inc/.
            $config = [
                'host'=>$form['host'], 'port'=>$form['port'], 'database'=>$form['database'],
                'username'=>$form['username'], 'password'=>$form['password'],
            ];
            $source = "<?php\nif (!defined('VS_INTERNAL')) { http_response_code(404); exit; }\nreturn " . var_export($config,true) . ";\n";
            $temporary = $configPath.'.tmp.'.bin2hex(random_bytes(5));
            if (file_put_contents($temporary, $source, LOCK_EX) === false) throw new RuntimeException('Konfigurationsdatei konnte nicht angelegt werden.');
            @chmod($temporary,0600);
            if (!rename($temporary,$configPath)) throw new RuntimeException('Konfigurationsdatei konnte nicht fertiggestellt werden.');
            @chmod($configPath,0600);
            $success = true;
            $installed = true;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            error_log('[Vereinssystem Installer] '.$e->getMessage());
            $errors[] = 'Einrichtung fehlgeschlagen: '. $e->getMessage();
        }
    }
}
function e(mixed $x): string { return htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
?>
<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Vereinssystem 2.1.0 – Installation</title>
<style>
:root{font-family:system-ui,-apple-system,Segoe UI,Arial,sans-serif;color:#14233a;background:#eef2f8}*{box-sizing:border-box}body{margin:0;padding:28px 15px}main{max-width:780px;margin:auto;background:white;border:1px solid #d9e0ec;border-radius:14px;box-shadow:0 7px 28px #1b304220;padding:clamp(20px,5vw,44px)}h1{margin-top:0;color:#193c68}h2{font-size:1.1em;margin:24px 0 10px;border-bottom:1px solid #ddd;padding-bottom:9px}p{line-height:1.55}label{display:block;margin:12px 0 5px;font-weight:600}input{display:block;width:100%;border:1px solid #9daab9;border-radius:7px;padding:11px;font:inherit}input:focus{outline:2px solid #2964a3}.grid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}@media(max-width:600px){.grid{grid-template-columns:1fr}}button,.go{display:inline-block;padding:12px 23px;background:#1c5c9a;border:0;border-radius:7px;color:#fff;font-weight:bold;text-decoration:none;cursor:pointer}.alert{padding:13px 18px;border-radius:7px;background:#fff0ed;border:1px solid #edb5ad}.ok{background:#e7f7ee;border-color:#b8dbc4}.muted{color:#526278;font-size:0.93rem}
</style></head><body><main>
<h1>Vereinssystem <small>2.1.0</small></h1>
<p>Installation für PHP 8 und MySQL/MariaDB mit PDO. Die Datenbank muss beim Hoster bereits angelegt sein. Die Tabellen erstellt der Assistent.</p>
<?php if ($success): ?>
<div class="alert ok"><strong>Installation abgeschlossen.</strong>
<p><?php if ($createdAdmin): ?>Das Administratorkonto wurde eingerichtet.<?php else: ?>Es waren bereits Mitglieder vorhanden; deren bestehende Zugangsdaten bleiben gültig. Alte MD5-Passwörter werden beim nächsten erfolgreichen Login aktualisiert.<?php endif; ?></p>
<p>Die Installation ist durch die erstellte Konfigurationsdatei gesperrt. Löschen Sie zusätzlich <code>install.php</code> vom Server, sobald alles funktioniert.</p></div>
<p><a class="go" href="login.php">Zum Login</a></p>
<?php elseif ($installed): ?>
<div class="alert ok">Diese Installation ist bereits eingerichtet. <code>inc/config.php</code> ist vorhanden. Aus Sicherheitsgründen ist eine erneute Einrichtung gesperrt.</div><p><a class="go" href="login.php">Zum Login</a></p>
<?php else: ?>
<?php if ($errors): ?><div class="alert"><b>Bitte prüfen:</b><ul><?php foreach($errors as $err): ?><li><?=e($err)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" autocomplete="off">
<h2>1 · Datenbank</h2><div class="grid">
<div><label for="host">Datenbankhost</label><input id="host" name="host" value="<?=e($_POST['host']??'localhost')?>" required></div>
<div><label for="port">Port</label><input id="port" name="port" inputmode="numeric" type="number" min="1" max="65535" value="<?=e($_POST['port']??'3306')?>" required></div>
<div><label for="database">Datenbankname</label><input id="database" name="database" value="<?=e($_POST['database']??'')?>" required></div>
<div><label for="username">Datenbankbenutzer</label><input id="username" name="username" value="<?=e($_POST['username']??'')?>" required></div>
</div><label for="password">Datenbankpasswort</label><input id="password" type="password" name="password" autocomplete="new-password">
<h2>2 · Verein und Administrator</h2>
<label for="club">Vereinsname</label><input id="club" name="club" value="<?=e($_POST['club']??'Mein Verein')?>" required>
<div class="grid"><div><label for="first">Vorname</label><input id="first" name="first" value="<?=e($_POST['first']??'')?>" required></div><div><label for="last">Nachname</label><input id="last" name="last" value="<?=e($_POST['last']??'')?>" required></div>
<div><label for="adminuser">Administrator-Login</label><input id="adminuser" name="adminuser" value="<?=e($_POST['adminuser']??'admin')?>" required></div>
<div><label for="adminemail">E-Mail</label><input id="adminemail" type="email" name="adminemail" value="<?=e($_POST['adminemail']??'')?>" required></div>
<div><label for="adminbirth">Geburtsdatum</label><input id="adminbirth" type="date" name="adminbirth" value="<?=e($_POST['adminbirth']??'')?>" required></div>
<div><label for="adminpass">Administrator-Passwort</label><input type="password" id="adminpass" name="adminpass" minlength="12" required autocomplete="new-password"></div>
<div><label for="adminpass2">Passwort wiederholen</label><input type="password" id="adminpass2" name="adminpass2" minlength="12" required autocomplete="new-password"></div>
</div>
<p class="muted">Bei einer bereits vorhandenen Vereinsdatenbank werden die Mitglieder nicht überschrieben und kein weiterer Administrator angelegt. Für die Erstinstallation mindestens 12 Zeichen als Passwort verwenden. HTTPS empfohlen.</p>
<button type="submit">Installieren</button></form>
<?php endif; ?>
</main></body></html>