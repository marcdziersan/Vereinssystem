<?php
/** Authenticated start page; only administrative roles receive club-wide data. */
declare(strict_types=1);

namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\AuthService;
use Vereinssystem\Auth\Roles;
use Vereinssystem\Core\{Csrf, Request, Response, View};
use Vereinssystem\Members\MemberRepository;

final class DashboardController extends Controller
{
    public function __construct(
        View $views, Csrf $csrf,
        private readonly ?AuthService $auth,
        private readonly ?MemberRepository $members,
        private readonly Roles $roles,
    ) { parent::__construct($views, $csrf); }

    public function index(Request $request): Response
    {
        $user = $this->auth?->currentUser();
        if ($user === null) return Response::redirect($request->url('/login'));
        $canList = $this->roles->allows($user, 'members.list');
        $stats = $canList ? $this->members?->statistics() : null;
        return $this->page($request, 'Dashboard', 'dashboard/index', [
            'user' => $user,
            'stats' => $stats,
            'membersUrl' => $request->url('/members'),
            'profileUrl' => $request->url('/members/view') . '&id=' . $user->id,
            'rightsUrl' => $request->url('/rights'),
            'canList' => $canList,
        ], $user);
    }
}
