<?php
/** Compose a deterministic, testable per-route middleware pipeline. */
declare(strict_types=1);
namespace Vereinssystem\Http\Middleware;
use Vereinssystem\Core\{Request,Response};
final class Stack
{
    /** @param callable(Request):Response $handler @return callable(Request):Response */
    public static function wrap(callable $handler, Middleware ...$middleware): callable
    {
        foreach (array_reverse($middleware) as $part) {
            $next = $handler;
            $handler = static fn(Request $request): Response => $part->handle($request, $next);
        }
        return $handler;
    }
}
