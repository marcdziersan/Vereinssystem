<?php
/**
 * The only PHP entry point exposed by the new application.
 * Configure the web server's document root to the public/ directory.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// Hide traces; the application logs failures on the server.
ini_set('display_errors', '0');
error_reporting(E_ALL);

$request = Vereinssystem\Core\Request::fromGlobals();
vs_app()->handle($request)->send();
