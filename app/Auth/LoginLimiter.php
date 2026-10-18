<?php
/** File-backed per-IP login throttle with flock and no public filesystem access. */
declare(strict_types=1);

namespace Vereinssystem\Auth;

use RuntimeException;

final class LoginLimiter
{
    public function __construct(private readonly string $directory) {}

    public function blocked(string $ip): bool
    {
        return $this->operate($ip, static fn(array $state): array => [$state, $state['blocked'] > time()]);
    }

    public function failure(string $ip): void
    {
        $this->operate($ip, static function (array $state): array {
            $state['count'] = ($state['expires'] < time() ? 0 : $state['count']) + 1;
            $state['expires'] = time() + 900;
            if ($state['count'] >= 5) {
                $state['blocked'] = time() + 900;
                $state['count'] = 0;
            }
            return [$state, null];
        });
    }

    public function success(string $ip): void
    {
        $this->operate($ip, static fn(array $state): array => [['count'=>0,'expires'=>0,'blocked'=>0], null]);
    }

    /** @template T @param callable(array{count:int,expires:int,blocked:int}):array{0:array,1:T} $operation @return T */
    private function operate(string $ip, callable $operation): mixed
    {
        // Static salt deliberately avoids logging raw IP addresses in the file name.
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Rate-limit storage not writable.');
        }
        $path = $this->directory . '/' . hash('sha256', $ip) . '.json';
        $file = fopen($path, 'c+');
        if ($file === false) throw new RuntimeException('Rate-limit file cannot be opened.');
        try {
            if (!flock($file, LOCK_EX)) throw new RuntimeException('Rate-limit lock failed.');
            $data = stream_get_contents($file);
            $decoded = is_string($data) ? json_decode($data, true) : null;
            $state = [
                'count'=>(int)($decoded['count'] ?? 0),
                'expires'=>(int)($decoded['expires'] ?? 0),
                'blocked'=>(int)($decoded['blocked'] ?? 0),
            ];
            [$next, $value] = $operation($state);
            rewind($file);
            ftruncate($file, 0);
            fwrite($file, (string)json_encode($next, JSON_THROW_ON_ERROR));
            fflush($file);
            flock($file, LOCK_UN);
            return $value;
        } finally {
            fclose($file);
        }
    }
}
