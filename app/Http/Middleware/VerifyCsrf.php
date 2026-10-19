<?php
/** Reject forged POST requests before any controller action is executed. */
declare(strict_types=1);
namespace Vereinssystem\Http\Middleware;
use Vereinssystem\Core\{Csrf,Request,Response};
final class VerifyCsrf implements Middleware
{
    public function __construct(private readonly Csrf $csrf) {}
    public function handle(Request $request, callable $next): Response
    {
        if (!$this->csrf->verify($request->inputString('_csrf'))) {
            return new Response('Formular abgelaufen', 419, ['Content-Type'=>'text/plain; charset=utf-8']);
        }
        return $next($request);
    }
}
