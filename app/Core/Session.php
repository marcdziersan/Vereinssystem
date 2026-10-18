<?php
/**
 * Secure session wrapper, unused by domain modules in this milestone.
 * Authentication will use it in a later, independent implementation stage.
 */
declare(strict_types=1);

namespace Vereinssystem\Core;

use LogicException;

final class Session
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (headers_sent()) {
            throw new LogicException('Cannot start session after response output.');
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $fallback;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }
}
