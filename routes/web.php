<?php
/** Explicit route registration; no legacy PHP endpoint or dynamic file routing. */
declare(strict_types=1);

use Vereinssystem\Core\Router;
use Vereinssystem\Controllers\{KernelController, AuthController, AccessController, DashboardController, MemberController, PlaceController, DepartmentController, InstallController};

return static function (
    Router $router,
    KernelController $kernel,
    AuthController $auth,
    AccessController $access,
    DashboardController $dashboard,
    MemberController $members,
    PlaceController $places,
    DepartmentController $departments,
    InstallController $install,
): void {
    $router->add('GET', '/', [$auth, 'home']);
    $router->add('GET', '/install', [$install, 'form']);
    $router->add('POST', '/install', [$install, 'submit']);
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
    // Addresses / event venues (MVC alpha.4)
    $router->add('GET', '/places', [$places,'index']);
    $router->add('GET', '/places/create', [$places,'create']);
    $router->add('GET', '/places/edit', [$places,'edit']);
    $router->add('POST', '/places/store', [$places,'save']);
    $router->add('POST', '/places/update', [$places,'save']);
    $router->add('POST', '/places/status', [$places,'status']);
    // Departments and membership relationships (MVC alpha.4)
    $router->add('GET', '/departments', [$departments,'index']);
    $router->add('GET', '/departments/view', [$departments,'show']);
    $router->add('GET', '/departments/create', [$departments,'create']);
    $router->add('GET', '/departments/edit', [$departments,'edit']);
    $router->add('POST', '/departments/store', [$departments,'save']);
    $router->add('POST', '/departments/update', [$departments,'save']);
    $router->add('POST', '/departments/assign', [$departments,'assign']);
    $router->add('POST', '/departments/remove', [$departments,'remove']);
    $router->add('POST', '/departments/status', [$departments,'status']);
};
