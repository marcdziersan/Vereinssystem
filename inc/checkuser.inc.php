<?php
require_once __DIR__.'/connect.inc.php';
vs_session_start();
if (empty($_SESSION['uid'])) { header('Location: login.php?after='.urlencode(base64_encode($_SERVER['REQUEST_URI'] ?? 'index.php'))); exit; }
$r = db_query('SELECT * FROM mitglieder WHERE mitglieder_id = ? LIMIT 1',[(int)$_SESSION['uid']]);
$user = db_fetch_assoc($r);
if (!$user || (int)$user['status'] !== 1 || (int)$user['rights'] === 0) {
 $_SESSION=[]; session_destroy(); header('Location: login.php?forbidden'); exit;
}
if (empty($_SESSION['vs_csrf'])) $_SESSION['vs_csrf']=bin2hex(random_bytes(32));
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !hash_equals((string)$_SESSION['vs_csrf'],(string)($_POST['vs_csrf'] ?? ''))) {
 http_response_code(403); exit('Sicherheitsprüfung fehlgeschlagen. Bitte Formular neu laden.');
}
