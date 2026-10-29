<?php
/**
 * PHP development-server adapter: `php -S 127.0.0.1:8080 -t public public/router.php`.
 * Static files are returned directly; every other path enters the MVC router.
 */
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';
// Match Apache's root deny rules when using `php -S`: inaccessible files
// outside public/ must never be silently treated as application routes.
$normalized = ltrim(str_replace('\\', '/', $path), '/');
if (preg_match('~(?:^|/)\.\.(?:/|$)~', $normalized)
    || preg_match('~^(?:app|bin|config|database|docs|resources|routes|storage|tests)(?:/|$)~i', $normalized)
    || preg_match('~^(?:\.git|\.env|\.htaccess|bootstrap\.php|README\.md|CHANGELOG\.md|LICENSE(?:-[^/]*)?\.md|THIRD-PARTY-NOTICES\.md)(?:$|/)~i', $normalized)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo "Zugriff verweigert.";
    return true;
}

$root = realpath(__DIR__);
$target = realpath(__DIR__ . '/' . ltrim($path, '/'));
if ($root !== false && $target !== false && str_starts_with($target, $root . DIRECTORY_SEPARATOR) && is_file($target) && basename($target) !== 'index.php' && basename($target) !== 'router.php' && !str_starts_with(basename($target), '.')) {
    return false;
}
require __DIR__ . '/index.php';
