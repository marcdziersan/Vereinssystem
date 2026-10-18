<?php
/** Explicit route registration; no legacy PHP endpoint or dynamic file routing. */
declare(strict_types=1);

use Vereinssystem\Core\Router;
use Vereinssystem\Controllers\{KernelController, AuthController, AccessController, DashboardController, MemberController};

return static function (
    Router $router,
    KernelController $kernel,
    AuthController $auth,
    AccessController $access,
    DashboardController $dashboard,
    MemberController $members,
): void {
    $router->add('GET', '/', [$auth, 'home']);
    $router->add('GET', '/status', [$kernel, 'status']);
    $router->add('GET', '/login', [$auth, 'form']);
    $router->add('POST', '/login', [$auth, 'authenticate']);
    $router->add('POST', '/logout', [$auth, 'logout']);
    $router->add('GET', '/dashboard', [$dashboard, 'index']);
    $router->add('GET', '/account', [$access, 'account']);
    $router->add('GET', '/rights', [$access, 'matrix']);
    $router->add('GET', '/admin/roles', [$access, 'manage']);
    $router->add('POST', '/admin/roles', [$access, 'change']);
    $router->add('GET', '/members', [$members, 'index']);
    $router->add('GET', '/members/view', [$members, 'show']);
    $router->add('GET', '/members/create', [$members, 'create']);
    $router->add('POST', '/members/store', [$members, 'store']);
    $router->add('GET', '/members/edit', [$members, 'edit']);
    $router->add('POST', '/members/update', [$members, 'update']);
    $router->add('POST', '/members/status', [$members, 'status']);
};
