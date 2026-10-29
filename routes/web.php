<?php
/** Explicit route registration; no legacy PHP endpoint or dynamic file routing. */
declare(strict_types=1);

use Vereinssystem\Core\{Router,Csrf};
use Vereinssystem\Auth\{AuthService,Roles};
use Vereinssystem\Http\Middleware\{Authenticated,Permission,Stack,VerifyCsrf};
use Vereinssystem\Controllers\{KernelController, AuthController, AccessController, DashboardController, MemberController, PlaceController, DepartmentController, InstallController, EventController, NewsController, FinanceController, CashController, CashReportController, ReceiptController, SelfServiceController, SystemSettingsController};

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
    CashReportController $reports, ReceiptController $receipts,
    SelfServiceController $selfService,
    SystemSettingsController $settings,
): void {
    $router->add('GET', '/', [$auth, 'home']);
    $router->add('GET', '/install', [$install, 'form']);
    $router->add('POST', '/install', Stack::wrap([$install, 'submit'], new VerifyCsrf($csrf)));
    $router->add('GET', '/status', [$kernel, 'status']);
    $router->add('GET', '/login', [$auth, 'form']);
    $router->add('POST', '/login', Stack::wrap([$auth, 'authenticate'], new VerifyCsrf($csrf)));
    $router->add('POST', '/logout', Stack::wrap([$auth, 'logout'], new VerifyCsrf($csrf)));
    $router->add('GET', '/dashboard', Stack::wrap([$dashboard, 'index'], new Authenticated($authService), new Permission($authService, $roles, 'dashboard.view')));
    $router->add('GET', '/account', Stack::wrap([$access, 'account'], new Authenticated($authService), new Permission($authService, $roles, 'account.view')));
    // Alpha 10.1: self-service only, with explicit auth/permission/CSRF guards.
    $router->add('GET', '/account/settings', Stack::wrap([$selfService, 'form'],
        new Authenticated($authService), new Permission($authService, $roles, 'account.profile')));
    $router->add('POST', '/account/settings/profile', Stack::wrap([$selfService, 'profile'],
        new Authenticated($authService), new Permission($authService, $roles, 'account.profile'),
        new VerifyCsrf($csrf)));
    $router->add('POST', '/account/settings/password', Stack::wrap([$selfService, 'password'],
        new Authenticated($authService), new Permission($authService, $roles, 'account.password'),
        new VerifyCsrf($csrf)));
    // Alpha 10.2: settings and audit are administration-only; writes require CSRF.
    $router->add('GET', '/admin/settings', Stack::wrap([$settings, 'index'],
        new Authenticated($authService),new Permission($authService,$roles,'settings.view')));
    $router->add('POST', '/admin/settings/save', Stack::wrap([$settings, 'save'],
        new Authenticated($authService),new Permission($authService,$roles,'settings.edit'),new VerifyCsrf($csrf)));
    $router->add('GET', '/admin/settings/logs', Stack::wrap([$settings, 'logs'],
        new Authenticated($authService),new Permission($authService,$roles,'settings.history')));
    $router->add('GET', '/rights', Stack::wrap([$access, 'matrix'], new Authenticated($authService), new Permission($authService, $roles, 'rights.view')));
    $router->add('GET', '/admin/roles', Stack::wrap([$access, 'manage'], new Authenticated($authService), new Permission($authService, $roles, 'rights.assign')));
    $router->add('POST', '/admin/roles', Stack::wrap([$access, 'change'], new Authenticated($authService), new Permission($authService, $roles, 'rights.assign'), new VerifyCsrf($csrf)));
    $router->add('GET', '/members', Stack::wrap([$members, 'index'], new Authenticated($authService), new Permission($authService, $roles, 'members.list')));
    $router->add('GET', '/members/view', Stack::wrap([$members, 'show'], new Authenticated($authService), new Permission($authService, $roles, 'members.self')));
    $router->add('GET', '/members/create', Stack::wrap([$members, 'create'], new Authenticated($authService), new Permission($authService, $roles, 'members.create')));
    $router->add('POST', '/members/store', Stack::wrap([$members, 'store'], new Authenticated($authService), new Permission($authService, $roles, 'members.create'), new VerifyCsrf($csrf)));
    $router->add('GET', '/members/edit', Stack::wrap([$members, 'edit'], new Authenticated($authService), new Permission($authService, $roles, 'members.edit')));
    $router->add('POST', '/members/update', Stack::wrap([$members, 'update'], new Authenticated($authService), new Permission($authService, $roles, 'members.edit'), new VerifyCsrf($csrf)));
    $router->add('POST', '/members/status', Stack::wrap([$members, 'status'], new Authenticated($authService), new Permission($authService, $roles, 'members.status'), new VerifyCsrf($csrf)));
    // Addresses / event venues (MVC alpha.4)
    $router->add('GET', '/places', Stack::wrap([$places, 'index'], new Authenticated($authService), new Permission($authService, $roles, 'places.list')));
    $router->add('GET', '/places/create', Stack::wrap([$places, 'create'], new Authenticated($authService), new Permission($authService, $roles, 'places.create')));
    $router->add('GET', '/places/edit', Stack::wrap([$places, 'edit'], new Authenticated($authService), new Permission($authService, $roles, 'places.edit')));
    $router->add('POST', '/places/store', Stack::wrap([$places, 'save'], new Authenticated($authService), new Permission($authService, $roles, 'places.create'), new VerifyCsrf($csrf)));
    $router->add('POST', '/places/update', Stack::wrap([$places, 'save'], new Authenticated($authService), new Permission($authService, $roles, 'places.edit'), new VerifyCsrf($csrf)));
    $router->add('POST', '/places/status', Stack::wrap([$places, 'status'], new Authenticated($authService), new Permission($authService, $roles, 'places.status'), new VerifyCsrf($csrf)));
    // Departments and membership relationships (MVC alpha.4)
    $router->add('GET', '/departments', Stack::wrap([$departments, 'index'], new Authenticated($authService), new Permission($authService, $roles, 'departments.list')));
    $router->add('GET', '/departments/view', Stack::wrap([$departments, 'show'], new Authenticated($authService), new Permission($authService, $roles, 'departments.view')));
    $router->add('GET', '/departments/create', Stack::wrap([$departments, 'create'], new Authenticated($authService), new Permission($authService, $roles, 'departments.create')));
    $router->add('GET', '/departments/edit', Stack::wrap([$departments, 'edit'], new Authenticated($authService), new Permission($authService, $roles, 'departments.edit')));
    $router->add('POST', '/departments/store', Stack::wrap([$departments, 'save'], new Authenticated($authService), new Permission($authService, $roles, 'departments.create'), new VerifyCsrf($csrf)));
    $router->add('POST', '/departments/update', Stack::wrap([$departments, 'save'], new Authenticated($authService), new Permission($authService, $roles, 'departments.edit'), new VerifyCsrf($csrf)));
    $router->add('POST', '/departments/assign', Stack::wrap([$departments, 'assign'], new Authenticated($authService), new Permission($authService, $roles, 'departments.assign'), new VerifyCsrf($csrf)));
    $router->add('POST', '/departments/remove', Stack::wrap([$departments, 'remove'], new Authenticated($authService), new Permission($authService, $roles, 'departments.assign'), new VerifyCsrf($csrf)));
    $router->add('POST', '/departments/status', Stack::wrap([$departments, 'status'], new Authenticated($authService), new Permission($authService, $roles, 'departments.status'), new VerifyCsrf($csrf)));
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
    // Alpha.9: all exports/downloads are authenticated, even though they use GET.
    // Mutating the receipt vault requires both permission AND CSRF middleware.
    $reportRoutes=[
        ['GET','/cash/report','index','cash.report'],
        ['GET','/cash/report/csv','export','cash.report'],
        ['GET','/cash/report/pdf','export','cash.report'],
    ];
    $receiptRoutes=[
        ['POST','/cash/receipt/upload','upload','cash.receipts.add'],
        ['GET','/cash/receipt/download','download','cash.receipts.view'],
    ];
    foreach($reportRoutes as [$method,$path,$methodName,$capability]){
        $router->add($method,$path,Stack::wrap([$reports,$methodName],
            new Authenticated($authService),new Permission($authService,$roles,$capability)));
    }
    foreach($receiptRoutes as [$method,$path,$methodName,$capability]){
        $guards=[new Authenticated($authService),new Permission($authService,$roles,$capability)];
        if($method==='POST')$guards[]=new VerifyCsrf($csrf);
        $router->add($method,$path,Stack::wrap([$receipts,$methodName],...$guards));
    }
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
