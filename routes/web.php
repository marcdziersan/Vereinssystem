<?php
/** Explicit route registration; no legacy PHP endpoint or dynamic file routing. */
declare(strict_types=1);

use Vereinssystem\Core\{Router,Csrf};
use Vereinssystem\Auth\{AuthService,Roles};
use Vereinssystem\Http\Middleware\{Authenticated,Permission,Stack,VerifyCsrf};
use Vereinssystem\Controllers\{KernelController, AuthController, AccessController, DashboardController, MemberController, PlaceController, DepartmentController, InstallController, EventController, NewsController, FinanceController, CashController};

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
    EventController $events, NewsController $news, FinanceController $finance, CashController $cash,
    ?AuthService $authService, Roles $roles, Csrf $csrf,
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
    // Alpha.5: Events/tasks are registered with explicit composable middleware.
    // Authenticated -> permission -> CSRF for each POST. No legacy endpoints.
    $eventRoutes = [
        ['GET','/events','index','events.list'],
        ['GET','/events/view','show','events.view'],
        ['GET','/events/create','create','events.create'],
        ['GET','/events/edit','edit','events.edit'],
        ['POST','/events/store','save','events.create'],
        ['POST','/events/update','save','events.edit'],
        ['POST','/events/status','status','events.status'],
        ['GET','/events/tasks/create','createTask','events.tasks.manage'],
        ['GET','/events/tasks/edit','editTask','events.tasks.manage'],
        ['POST','/events/tasks/save','saveTask','events.tasks.manage'],
        ['POST','/events/tasks/archive','archiveTask','events.tasks.manage'],
    ];
    // Alpha.6: explicit news routes with the same composable, server-side middleware.
    $newsRoutes = [
        ['GET','/news','index','news.list'],
        ['GET','/news/view','show','news.view'],
        ['GET','/news/create','create','news.create'],
        ['GET','/news/edit','edit','news.edit'],
        ['POST','/news/store','save','news.create'],
        ['POST','/news/update','save','news.edit'],
        ['POST','/news/archive','archive','news.archive'],
        ['GET','/news/archived','archived','news.archive.list'],
        ['POST','/news/restore','restore','news.restore'],
    ];
    // Alpha.7: all finance changes are POST-only and require role + CSRF.
    $financeRoutes=[
        ['GET','/finance','index','finance.view'],
        ['GET','/finance/tariffs','tariffs','finance.tariffs'],
        ['POST','/finance/payment','action','finance.manage'],
        ['POST','/finance/claim','action','finance.manage'],
        ['POST','/finance/reconcile','action','finance.manage'],
        ['POST','/finance/void-payment','action','finance.void'],
        ['POST','/finance/void-claim','action','finance.void'],
        ['POST','/finance/generate','action','finance.generate'],
        ['POST','/finance/rate','action','finance.tariffs'],
    ];
    // Alpha.8: all cash mutations are guarded by role and CSRF middleware.
    $cashRoutes=[
        ['GET','/cash','index','cash.view'],
        ['POST','/cash/account','action','cash.accounts'],
        ['POST','/cash/account/update','action','cash.accounts'],
        ['POST','/cash/book','action','cash.manage'],
        ['POST','/cash/transfer','action','cash.manage'],
        ['POST','/cash/void','action','cash.void'],
    ];
    foreach($cashRoutes as [$method,$path,$methodName,$capability]){
        $guards=[new Authenticated($authService),new Permission($authService,$roles,$capability)];
        if($method==='POST')$guards[]=new VerifyCsrf($csrf);
        $router->add($method,$path,Stack::wrap([$cash,$methodName],...$guards));
    }
    foreach($financeRoutes as [$method,$path,$methodName,$capability]){
        $guards=[new Authenticated($authService),new Permission($authService,$roles,$capability)];
        if($method==='POST')$guards[]=new VerifyCsrf($csrf);
        $router->add($method,$path,Stack::wrap([$finance,$methodName],...$guards));
    }
    foreach ($newsRoutes as [$method,$path,$methodName,$capability]) {
        $guards=[new Authenticated($authService),new Permission($authService,$roles,$capability)];
        if($method==='POST')$guards[]=new VerifyCsrf($csrf);
        $router->add($method,$path,Stack::wrap([$news,$methodName],...$guards));
    }
    foreach ($eventRoutes as [$method,$path,$methodName,$capability]) {
        $guards = [new Authenticated($authService), new Permission($authService,$roles,$capability)];
        if ($method === 'POST') $guards[] = new VerifyCsrf($csrf);
        $router->add($method,$path,Stack::wrap([$events,$methodName],...$guards));
    }
};
