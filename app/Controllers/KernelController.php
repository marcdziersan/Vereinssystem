<?php
/**
 * Kernel demonstration controller – not a member or authentication feature.
 * This page exists only so the new MVC stack can be inspected in the browser.
 */
declare(strict_types=1);

namespace Vereinssystem\Controllers;

use Vereinssystem\Core\Config;
use Vereinssystem\Core\Request;
use Vereinssystem\Core\Response;
use Vereinssystem\Core\View;

final class KernelController
{
    public function __construct(private readonly View $views, private readonly Config $config) {}

    public function index(Request $request): Response
    {
        $content = $this->views->render('kernel/index', [
            'version' => $this->config->requireString('version'),
            'statusUrl' => $request->url('/status'),
        ]);
        return new Response($this->views->render('layouts/app', [
            'title' => 'MVC-Kernel',
            'content' => $content,
            'homeUrl' => $request->url('/'),
            'cssUrl' => $request->asset('css/vs-ui.css'),
            'jsUrl' => $request->asset('js/vs-ui.js'),
            'logoUrl' => $request->asset('images/logo_head.png'),
        ]), 200, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store']);
    }

    public function status(Request $request): Response
    {
        return Response::json(['application' => 'Vereinssystem MVC', 'version' => $this->config->requireString('version'), 'status' => 'ok']);
    }
}
