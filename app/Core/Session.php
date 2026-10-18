<?php
/** Secure PHP session lifecycle. CSRF and authentication share the same session. */
declare(strict_types=1);
namespace Vereinssystem\Core;

use LogicException;

final class Session
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        if (headers_sent()) throw new LogicException('Session started after output.');
        ini_set('session.use_strict_mode','1');
        ini_set('session.use_only_cookies','1');
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax',
        ]);
        session_start();
    }

    public function regenerate(): void { $this->start(); session_regenerate_id(true); }
    public function get(string $key,mixed $fallback=null): mixed { $this->start(); return $_SESSION[$key] ?? $fallback; }
    public function set(string $key,mixed $value): void { $this->start(); $_SESSION[$key]=$value; }
    public function forget(string $key): void { $this->start(); unset($_SESSION[$key]); }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'=>time()-3600,'path'=>$params['path'],
                'domain'=>$params['domain'],'secure'=>$params['secure'],
                'httponly'=>$params['httponly'],'samesite'=>$params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}
