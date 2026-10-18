<?php
/** Central route registry. Only the kernel demo and status endpoint exist. */
declare(strict_types=1);

use Vereinssystem\Controllers\KernelController;
use Vereinssystem\Core\Router;

return static function (Router $router, KernelController $controller): void {
    $router->add('GET', '/', [$controller, 'index']);
    $router->add('GET', '/status', [$controller, 'status']);
};
