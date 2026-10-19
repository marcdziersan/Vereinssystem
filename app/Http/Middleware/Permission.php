<?php
/** Enforce a centrally declared role capability on the server, not only in HTML. */
declare(strict_types=1);
namespace Vereinssystem\Http\Middleware;
use Vereinssystem\Auth\{AuthService,Roles};
use Vereinssystem\Core\{Request,Response};
final class Permission implements Middleware
{
    public function __construct(private readonly ?AuthService $auth, private readonly Roles $roles, private readonly string $capability) {}
    public function handle(Request $request, callable $next): Response
    {
        $user = $this->auth?->currentUser();
        if ($user === null) return Response::redirect($request->url('/login'));
        if (!$this->roles->allows($user, $this->capability)) return new Response('Kein Zugriff', 403, ['Content-Type'=>'text/plain; charset=utf-8']);
        return $next($request);
    }
}
