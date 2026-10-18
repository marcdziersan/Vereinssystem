<?php
/**
 * MVC composition root. Bootstraps infrastructure, registers the first routes,
 * and centralizes error handling without exposing stack traces to the browser.
 */
declare(strict_types=1);

namespace Vereinssystem\Core;

use Throwable;
use Vereinssystem\Controllers\KernelController;

final class Application
{
    private readonly Router $router;
    private readonly View $views;
    private readonly Config $config;

    private function __construct(string $root)
    {
        $this->config = new Config(require $root . '/config/app.php');
        $this->router = new Router();
        $this->views = new View($root . '/resources/views');
        $controller = new KernelController($this->views, $this->config);
        // Fixed routes; the first milestone intentionally has no domain routes.
        $register = require $root . '/routes/web.php';
        $register($this->router, $controller);
    }

    public static function fromProjectRoot(string $root): self
    {
        return new self($root);
    }

    /** Handle a request without global output, enabling low-cost CLI smoke tests. */
    public function handle(Request $request): Response
    {
        try {
            $response = $this->router->dispatch($request);
            if ($response->status === 404 || $response->status === 405) {
                $body = $this->views->render('errors/status', [
                    'status' => $response->status,
                    'message' => $response->status === 405 ? 'Methode nicht erlaubt' : 'Seite nicht gefunden',
                    'url' => $request->url('/'),
                    'asset' => $request->asset('css/vs-ui.css'),
                ]);
                return new Response($body, $response->status, $response->headers + ['Content-Type' => 'text/html; charset=utf-8']);
            }
            return $response;
        } catch (Throwable $exception) {
            // Real deployments configure PHP error_log outside of public/.
            error_log('[Vereinssystem/MVC] ' . $exception::class . ': ' . $exception->getMessage());
            $body = $this->views->render('errors/status', [
                'status' => 500,
                'message' => 'Interner Fehler',
                'url' => $request->url('/'),
                'asset' => $request->asset('css/vs-ui.css'),
            ]);
            return new Response($body, 500);
        }
    }
}
