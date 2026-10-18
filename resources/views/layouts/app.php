<?php
/**
 * Main HTML shell. All markup is server-rendered, mobile-first and accessible.
 * The $content fragment comes ONLY from a trusted View-rendered PHP template;
 * all user-supplied data inside child templates must be escaped separately.
 * @var string $title
 * @var string $content
 * @var string $homeUrl
 * @var string $cssUrl
 * @var string $jsUrl
 * @var string $logoUrl
 */
declare(strict_types=1);

use Vereinssystem\Core\View;
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#101e33">
    <meta name="description" content="Vereinssystem MVC – eigenständiger, mobiler PHP-Kernel">
    <title><?= View::escape($title) ?> | Vereinssystem</title>
    <link rel="icon" href="<?= View::escape($logoUrl) ?>" type="image/png">
    <link rel="stylesheet" href="<?= View::escape($cssUrl) ?>">
    <script defer src="<?= View::escape($jsUrl) ?>"></script>
</head>
<body class="vs-app">
    <!-- Accessible skip link: keyboard users bypass the repeated header. -->
    <a class="vs-skip-link" href="#main">Zum Inhalt springen</a>

    <header class="vs-header">
        <div class="vs-container vs-header__inner">
            <a href="<?= View::escape($homeUrl) ?>" class="vs-brand" aria-label="Vereinssystem, Startseite">
                <img src="<?= View::escape($logoUrl) ?>" alt="Vereinssystem" width="900" height="131">
            </a>
            <span class="vs-header__tag">MVC-Kernel</span>
            <button type="button" class="vs-icon-button" data-vs-theme-toggle aria-label="Farbschema ändern" title="Farbschema ändern">
                <svg aria-hidden="true" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </button>
        </div>
    </header>

    <main id="main" class="vs-container vs-main" tabindex="-1">
        <?= $content /* Internal trusted View fragment. Not arbitrary user HTML. */ ?>
    </main>

    <footer class="vs-footer">
        <div class="vs-container vs-footer__inner">
            <span>Vereinssystem · MVC-Neuentwicklung</span>
            <span>Originalprojekt 2012/2013 · Weiterentwicklung seit 2026</span>
        </div>
    </footer>
</body>
</html>
