<?php
/**
 * Vereinssystem 2.3.0 – Modul: login process.
 * Pfad: login_process.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
require_once __DIR__ . '/inc/connect.inc.php';
vs_session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php'); exit; }
if (!hash_equals((string)($_SESSION['vs_login_token'] ?? ''), (string)($_POST['vs_login_token'] ?? '')) || empty($_SESSION['vs_login_token'])) {
    header('Location: login.php?fail=data'); exit;
}
if ((int)($_SESSION['vs_login_block_until'] ?? 0) > time()) {
    header('Location: login.php?fail=wait'); exit;
}
if ((int)$settings['usecaptchalogin'] === 1) {
    if (empty($_SESSION['vs_captcha']) || !hash_equals(strtolower((string) $_SESSION['vs_captcha']), strtolower(trim((string)($_POST['captcha'] ?? ''))))) {
        header('Location: login.php?fail=captcha'); exit;
    }
    unset($_SESSION['vs_captcha']);
}
$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['psword'] ?? '');
$found = ($username !== '' && $password !== '') ? db_query(
    'SELECT * FROM `mitglieder` WHERE (`username` = ? OR `mitgliedsnummer` = ?) LIMIT 2',
    [$username, $username]
) : false;
$account = db_num_rows($found) === 1 ? db_fetch_assoc($found) : false;
$valid = false;
if ($account) {
    $hash = (string)$account['passwort'];
    $valid = password_verify($password, $hash);
    $legacy = !$valid && preg_match('/^[a-f0-9]{32}$/i', $hash) && hash_equals(strtolower($hash), md5($password));
    if ($legacy) $valid = true;
    if ($valid && ($legacy || password_needs_rehash($hash, PASSWORD_DEFAULT))) {
        db_query('UPDATE `mitglieder` SET `passwort` = ? WHERE `mitglieder_id` = ?', [password_hash($password, PASSWORD_DEFAULT), (int)$account['mitglieder_id']]);
    }
}
if (!$valid || (int)$account['status'] !== 1 || (int)$account['rights'] === 0) {
    $_SESSION['vs_login_failures'] = (int)($_SESSION['vs_login_failures'] ?? 0) + 1;
    if ($_SESSION['vs_login_failures'] >= 5) {
        $_SESSION['vs_login_block_until'] = time() + 900;
        $_SESSION['vs_login_failures'] = 0;
    }
    header('Location: login.php?fail=data'); exit;
}
$_SESSION['vs_login_failures'] = 0;
unset($_SESSION['vs_login_block_until'], $_SESSION['vs_login_token']);
session_regenerate_id(true);
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
db_query('UPDATE `mitglieder` SET `lastlogintime` = `currentlogintime`, `lastloginip` = `currentloginip`, `currentlogintime` = ?, `currentloginip` = ? WHERE `mitglieder_id` = ?', [time(),$ip,(int)$account['mitglieder_id']]);
$_SESSION['uid'] = (int)$account['mitglieder_id'];
$_SESSION['loginip'] = $ip;
$_SESSION['freshlogin'] = true;
$redirect = 'index.php';
if (isset($_POST['after'])) {
    $path = base64_decode((string)$_POST['after'], true);
    // Nur Pfade dieser Installation. Externe Weiterleitungen oder //hostname verbieten.
    if (is_string($path) && preg_match('~^/(?!/)[a-zA-Z0-9_/?&=%.,+\-]*$~D', $path)) $redirect = $path;
}
header('Location: '.$redirect); exit;