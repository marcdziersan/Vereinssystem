<?php
/**
 * Vereinssystem 2.3.0 – Modul: captcha.
 * Pfad: captcha.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
require_once __DIR__.'/inc/session.php';
vs_session_start();
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
if (!extension_loaded('gd')) { http_response_code(503); exit; }
if (!isset($_SESSION['vs_captcha'])) $_SESSION['vs_captcha'] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
$code = (string)$_SESSION['vs_captcha'];
$im = imagecreatetruecolor(180, 58);
$bg = imagecolorallocate($im, 246, 248, 252);
$fg = imagecolorallocate($im, 27, 41, 64);
imagefill($im,0,0,$bg);
imagestring($im, 5, 53, 20, $code, $fg);
imagepng($im);
imagedestroy($im);