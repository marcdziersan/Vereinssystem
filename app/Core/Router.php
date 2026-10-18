<?php
/**
 * Exact-match HTTP router. URL strings are never treated as class/file names.
 * The small first version deliberately omits wildcard and parameter routing.
 */
declare(strict_types=1);

namespace Vereinssystem\Core;

use InvalidArgumentException;

final class Router
{
    /** @var array<string, callable(Request): Response> */
    private array $routes = [];

    /** @var array<string, list<string>> */
    private array $methods = [];

    /** @param callable(Request): Response $handler */
    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $path = Request::normalizePath($path);
        if (!preg_match('/^(GET|POST|PUT|PATCH|DELETE)$/D', $method) || $path === '/__invalid-route') {
            throw new InvalidArgumentException('Invalid route definition.');
        }
        $key = $method . ' ' . $path;
        if (isset($this->routes[$key])) {
            throw new InvalidArgumentException('Duplicate route: ' . $key);
        }
        $this->routes[$key] = $handler;
        $this->methods[$path] ??= [];
        $this->methods[$path][] = $method;
    }

    /** The application supplies the common 404/405 views. */
    public function dispatch(Request $request): Response
    {
        $key = $request->method . ' ' . $request->path;
        if (isset($this->routes[$key])) {
            return ($this->routes[$key])($request);
        }
        if (isset($this->methods[$request->path])) {
            return new Response('', 405, ['Allow' => implode(', ', $this->methods[$request->path])]);
        }
        return new Response('', 404);
    }
}
