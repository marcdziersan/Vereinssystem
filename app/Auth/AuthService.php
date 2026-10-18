<?php
/** Authentication and current-user refresh; no direct HTML or HTTP dependencies. */
declare(strict_types=1);

namespace Vereinssystem\Auth;

use Vereinssystem\Core\Session;
use Vereinssystem\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Session $session,
        private readonly LoginLimiter $limiter
    ) {}

    public function currentUser(): ?User
    {
        $id = $this->session->get('mvc_uid');
        if (!is_int($id) || $id < 1) return null;
        $user = $this->users->byId($id);
        if (!$user?->active()) {
            $this->session->forget('mvc_uid');
            return null;
        }
        return $user;
    }

    public function login(string $identifier, string $password, string $ip): bool
    {
        // Limit work for abusive payloads and use a generic failure message.
        if ($this->limiter->blocked($ip)) return false;
        $identifier = trim($identifier);
        if ($identifier === '' || strlen($identifier) > 150 || $password === '' || strlen($password) > 4096) {
            $this->limiter->failure($ip);
            return false;
        }
        $user = $this->users->byIdentifier($identifier);
        $hash = $user?->passwordHash ?? '';
        $ok = $user !== null && password_verify($password, $hash);
        // Historical 2013 hashes are upgraded only upon a successful login.
        $oldMd5 = !$ok && $user !== null && preg_match('/^[a-f0-9]{32}$/iD', $hash)
            && hash_equals(strtolower($hash), md5($password));
        if ((!$ok && !$oldMd5) || !$user?->active()) {
            $this->limiter->failure($ip);
            return false;
        }
        if ($oldMd5 || password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->users->replaceHash($user->id, password_hash($password, PASSWORD_DEFAULT));
        }
        $this->session->regenerate();
        $this->session->forget('_vs_csrf');
        $this->session->set('mvc_uid', $user->id);
        $this->users->recordLogin($user->id, $ip);
        $this->limiter->success($ip);
        return true;
    }

    public function logout(): void
    {
        $this->session->destroy();
    }
}
