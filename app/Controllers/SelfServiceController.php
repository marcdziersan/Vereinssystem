<?php
/** Alpha 10.1: own account changes only; never reads a member ID from HTTP. */
declare(strict_types=1);

namespace Vereinssystem\Controllers;

use DomainException;
use Vereinssystem\Accounts\AccountService;
use Vereinssystem\Auth\AuthService;
use Vereinssystem\Auth\User;
use Vereinssystem\Core\{Csrf,Request,Response,Session,View};

final class SelfServiceController extends Controller
{
    public function __construct(
        View $views, Csrf $csrf,
        private readonly ?AuthService $auth,
        private readonly ?AccountService $accounts,
        private readonly Session $session
    ) { parent::__construct($views, $csrf); }

    private function user(): User
    {
        return $this->auth?->currentUser() ?? throw new \LogicException('Authentication middleware missing.');
    }

    public function form(Request $request): Response
    {
        $user = $this->user();
        $flash = $this->session->get('account_notice', null);
        $this->session->forget('account_notice');
        return $this->page($request, 'Zugangsdaten ändern', 'auth/account-settings', [
            'profile' => $this->accounts?->profile($user),
            'notice' => is_array($flash) ? $flash : null,
            'profileUrl' => $request->url('/account/settings/profile'),
            'passwordUrl' => $request->url('/account/settings/password'),
            'accountUrl' => $request->url('/account'),
            'csrfToken' => $this->csrf->token(),
        ], $user);
    }

    public function profile(Request $request): Response
    {
        try {
            $this->service()->updateProfile(
                $this->user(),
                $request->inputString('current_password'),
                $request->inputString('username'),
                $request->inputString('email'),
                $request->inputString('mobile')
            );
            $this->notify('success', 'Zugangsdaten wurden gespeichert.');
        } catch (DomainException $exception) {
            $this->notify('error', $exception->getMessage());
        }
        return Response::redirect($request->url('/account/settings'));
    }

    public function password(Request $request): Response
    {
        try {
            $this->service()->changePassword(
                $this->user(),
                $request->inputString('current_password'),
                $request->inputString('new_password'),
                $request->inputString('confirm_password')
            );
            // Invalidate the old session ID and CSRF secret after credential rotation.
            $this->session->regenerate();
            $this->session->forget('_vs_csrf');
            $this->notify('success', 'Passwort wurde geändert.');
        } catch (DomainException $exception) {
            $this->notify('error', $exception->getMessage());
        }
        return Response::redirect($request->url('/account/settings'));
    }

    private function service(): AccountService
    {
        return $this->accounts ?? throw new DomainException('Kontopflege derzeit nicht verfügbar.');
    }

    private function notify(string $type, string $message): void
    {
        $this->session->set('account_notice', ['type' => $type, 'message' => $message]);
    }
}