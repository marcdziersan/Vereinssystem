<?php
/** Explicit HTTP route list; GET renders, POST changes session or account rights. */
declare(strict_types=1);
use Vereinssystem\Core\Router;
use Vereinssystem\Controllers\{KernelController,AuthController,AccessController};
return static function(Router $router,KernelController $kernel,AuthController $auth,AccessController $access): void {
    $router->add('GET','/',[$auth,'home']);
    $router->add('GET','/status',[$kernel,'status']);
    $router->add('GET','/login',[$auth,'form']);
    $router->add('POST','/login',[$auth,'authenticate']);
    $router->add('POST','/logout',[$auth,'logout']);
    $router->add('GET','/account',[$access,'account']);
    $router->add('GET','/rights',[$access,'matrix']);
    $router->add('GET','/admin/roles',[$access,'manage']);
    $router->add('POST','/admin/roles',[$access,'change']);
};
