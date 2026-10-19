<?php
/** HTTP middleware contract: decorate a route handler without modifying MVC controllers. */
declare(strict_types=1);
namespace Vereinssystem\Http\Middleware;
use Vereinssystem\Core\{Request,Response};
interface Middleware
{
    /** @param callable(Request):Response $next */
    public function handle(Request $request, callable $next): Response;
}
