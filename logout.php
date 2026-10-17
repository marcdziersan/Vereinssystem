<?php
require_once __DIR__ . '/inc/session.php';
vs_session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $c = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'=>time()-42000, 'path'=>$c['path'], 'domain'=>$c['domain'],
        'secure'=>$c['secure'], 'httponly'=>$c['httponly'], 'samesite'=>'Lax'
    ]);
}
session_destroy();
header('Location: login.php?loggedout');
exit;
