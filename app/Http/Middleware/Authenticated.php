<?php
/** Redirect anonymous visitors before executing any protected route. */
declare(strict_types=1);
namespace Vereinssystem\Http\Middleware;
use Vereinssystem\Auth\AuthService;
use Vereinssystem\Core\{Request,Response};
final class Authenticated implements Middleware
{
    public function __construct(private readonly ?AuthService $auth) {}
    public function handle(Request $request, callable $next): Response
    {
        if ($this->auth?->currentUser() === null) return Response::redirect($request->url('/login'));
        return $next($request);
    }
}
