<?php
/** Login/logout HTTP workflow; all credential checks belong to AuthService. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\AuthService;
use Vereinssystem\Core\Csrf;
use Vereinssystem\Core\Request;
use Vereinssystem\Core\Response;
use Vereinssystem\Core\View;

final class AuthController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth)
    { parent::__construct($views,$csrf); }

    public function home(Request $request): Response
    {
        return Response::redirect($request->url($this->auth?->currentUser() ? '/account' : '/login'));
    }

    public function form(Request $request): Response
    {
        if ($this->auth?->currentUser()) return Response::redirect($request->url('/account'));
        return $this->loginPage($request);
    }

    private function loginPage(Request $request,string $error='',int $status=200): Response
    {
        return $this->page($request,'Anmelden','auth/login',[
            'csrfToken'=>$this->csrf->token(),
            'loginUrl'=>$request->url('/login'),
            'error'=>$error,
            'configured'=>$this->auth!==null,
        ],null,$status);
    }

    public function authenticate(Request $request): Response
    {
        if (!$this->csrf->verify($request->inputString('_csrf'))) return $this->badToken($request);
        if ($this->auth === null) return $this->loginPage($request,'Die Datenbank ist noch nicht konfiguriert.',503);
        if ($this->auth->login($request->inputString('identifier'),$request->inputString('password'),$request->remoteIp)) {
            return Response::redirect($request->url('/account'));
        }
        return $this->loginPage($request,'Anmeldung fehlgeschlagen. Bitte Zugangsdaten prüfen.',401);
    }

    public function logout(Request $request): Response
    {
        if (!$this->csrf->verify($request->inputString('_csrf'))) return $this->badToken($request);
        $this->auth?->logout();
        return Response::redirect($request->url('/login'));
    }
}
