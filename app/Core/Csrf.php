<?php
/** Stateless token-checking interface backed by the secure PHP session. */
declare(strict_types=1);

namespace Vereinssystem\Core;

final class Csrf
{
    public function __construct(private readonly Session $session) {}

    public function token(): string
    {
        $token = $this->session->get('_vs_csrf');
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('_vs_csrf', $token);
        }
        return $token;
    }

    public function verify(?string $candidate): bool
    {
        return is_string($candidate) && hash_equals($this->token(), $candidate);
    }
}
