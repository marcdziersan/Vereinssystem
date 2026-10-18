<?php
/**
 * HTTP request snapshot.
 * Data from superglobals is copied once; business controllers later receive a
 * Request instead of querying $_GET/$_POST/$_SERVER throughout the codebase.
 */
declare(strict_types=1);

namespace Vereinssystem\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query Read-only snapshot of URL parameters.
     * @param array<string, mixed> $body  Parsed form payload.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query = [],
        private readonly array $body = [],
        public readonly string $scriptName = '/index.php'
    ) {}

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        // ?r=/status also works without mod_rewrite and on shared hosting.
        $forced = $_GET['r'] ?? null;
        if (is_string($forced)) {
            $path = $forced;
        } else {
            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
            $rawPath = parse_url($uri, PHP_URL_PATH);
            $path = is_string($rawPath) ? rawurldecode($rawPath) : '/';
            $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            if ($base !== '' && $base !== '.' && str_starts_with($path, $base . '/')) {
                $path = substr($path, strlen($base));
            }
            if ($path === '/index.php') {
                $path = '/';
            }
        }

        return new self($method, self::normalizePath($path), $_GET, $_POST, $scriptName);
    }

    /** No filesystem paths, Unicode confusables or controller names in routes. */
    public static function normalizePath(string $path): string
    {
        if ($path === '/') {
            return '/';
        }
        if (!preg_match('~^/(?:[a-z0-9-]+/)*[a-z0-9-]+/?$~D', $path)) {
            return '/__invalid-route';
        }
        return rtrim($path, '/');
    }

    public function queryString(string $key, string $fallback = ''): string
    {
        $value = $this->query[$key] ?? $fallback;
        return is_string($value) ? $value : $fallback;
    }

    public function inputString(string $key, string $fallback = ''): string
    {
        $value = $this->body[$key] ?? $fallback;
        return is_string($value) ? $value : $fallback;
    }

    /** Absolute-path link back to this install; query routes work everywhere. */
    public function url(string $route = '/'): string
    {
        return $this->scriptName . '?r=' . rawurlencode(self::normalizePath($route));
    }

    /** Prefix for CSS, JS and images regardless of installation subdirectory. */
    public function asset(string $relativePath): string
    {
        if (!preg_match('~^[a-z0-9][a-z0-9/._-]*$~iD', $relativePath) || str_contains($relativePath, '..')) {
            throw new \InvalidArgumentException('Invalid public asset path.');
        }
        $base = rtrim(str_replace('\\', '/', dirname($this->scriptName)), '/');
        return ($base === '.' ? '' : $base) . '/assets/' . $relativePath;
    }
}
