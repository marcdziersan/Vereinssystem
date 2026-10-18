<?php
/**
 * MVC bootstrap – only infrastructure is initialized here.
 *
 * No authentication, member management, SQL queries or legacy PHP pages are
 * loaded in this first milestone. A PSR-4-like class map is intentionally
 * implemented without external package managers.
 */
declare(strict_types=1);

const VS_ROOT = __DIR__;

spl_autoload_register(static function (string $class): void {
    $prefix = 'Vereinssystem\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*(?:\\\\[A-Za-z][A-Za-z0-9_]*)*$/D', $relative)) {
        throw new RuntimeException('Invalid class name.');
    }

    $file = VS_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

/** Return a new application for the web entry point or CLI checks. */
function vs_app(): Vereinssystem\Core\Application
{
    return Vereinssystem\Core\Application::fromProjectRoot(VS_ROOT);
}
