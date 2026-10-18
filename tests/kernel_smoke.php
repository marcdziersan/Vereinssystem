<?php
/**
 * Deterministic Core smoke checks without MySQL or browser dependencies.
 * Execute: `php tests/kernel_smoke.php`. All assertions exit non-zero on fail.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Vereinssystem\Core\Application;
use Vereinssystem\Core\Request;
use Vereinssystem\Core\View;

$checks = 0;
function check(bool $condition, string $description): void
{
    global $checks;
    ++$checks;
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
    fwrite(STDOUT, "OK: {$description}\n");
}

$app = Application::fromProjectRoot(dirname(__DIR__));
$start = $app->handle(new Request('GET', '/', [], [], '/index.php'));
check($start->status === 200, 'Start route returns 200');
check(str_contains($start->body, 'MVC-Kernel'), 'Kernel view is rendered');
check(str_contains($start->body, 'logo_head.png'), 'Historical logo is referenced');
check(str_contains($start->body, 'vs-ui.css'), 'Mobile-first UI is loaded');
check(str_contains($start->body, 'lang="de"'), 'German language is declared');

$json = $app->handle(new Request('GET', '/status'));
check($json->status === 200, 'JSON status returns 200');
$data = json_decode($json->body, true, 512, JSON_THROW_ON_ERROR);
check($data['status'] === 'ok', 'JSON status payload');
check($data['version'] === '3.0.0-alpha.1', 'Version reported');

$missing = $app->handle(new Request('GET', '/does-not-exist'));
check($missing->status === 404, 'Missing route returns 404');
check(str_contains($missing->body, 'Seite nicht gefunden'), '404 view rendered');

$wrongMethod = $app->handle(new Request('POST', '/status'));
check($wrongMethod->status === 405, 'Wrong method returns 405');
check(($wrongMethod->headers['Allow'] ?? '') === 'GET', '405 includes Allow header');

check(Request::normalizePath('/test/..') === '/__invalid-route', 'Traversal-like routes rejected');
check(Request::normalizePath('/status/') === '/status', 'Trailing slash normalized');
check(View::escape('<script>') === '&lt;script&gt;', 'HTML output escaped');
check((new Request('GET', '/', [], [], '/verein/public/index.php'))->asset('css/vs-ui.css') === '/verein/public/assets/css/vs-ui.css', 'Subdirectory asset URL works');
check((new Request('GET', '/', [], [], '/verein/public/index.php'))->url('/status') === '/verein/public/index.php?r=%2Fstatus', 'Query routing works without rewrite');

check(!is_file(dirname(__DIR__) . '/login.php'), 'No legacy/login entry point in new root');
check(!is_file(dirname(__DIR__) . '/members.php'), 'No legacy members entry point in new root');
check(!is_file(dirname(__DIR__) . '/app/Controllers/AuthController.php'), 'No domain authentication controller yet');

fwrite(STDOUT, "PASS: {$checks} kernel checks.\n");
