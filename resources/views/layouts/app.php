<?php
/**
 * Mobile-first shared HTML layout: semantic navigation and POST-only logout.
 * $content is a trusted View fragment; dynamic values use View::escape().
 */
declare(strict_types=1);
use Vereinssystem\Core\View;
use Vereinssystem\Auth\Roles;
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#101e33">
    <title><?= View::escape($title) ?> | Vereinssystem</title>
    <link rel="icon" href="<?= View::escape($logoUrl) ?>" type="image/png">
    <link rel="stylesheet" href="<?= View::escape($cssUrl) ?>">
    <script defer src="<?= View::escape($jsUrl) ?>"></script>
</head>
<body class="vs-app">
    <a class="vs-skip-link" href="#main">Zum Inhalt springen</a>
    <header class="vs-header">
        <div class="vs-container vs-header__inner">
            <a href="<?= View::escape($homeUrl) ?>" class="vs-brand" aria-label="Vereinssystem, Startseite">
                <img src="<?= View::escape($logoUrl) ?>" alt="Vereinssystem" width="900" height="131">
            </a>
            <span class="vs-header__tag">MVC alpha.5</span>
            <button type="button" class="vs-icon-button" data-vs-theme-toggle aria-label="Farbschema ändern" title="Farbschema ändern">◐</button>
        </div>
    </header>
    <?php if($user!==null): ?>
    <nav class="vs-nav" aria-label="Hauptnavigation">
        <div class="vs-container vs-nav__inner">
            <a href="<?= View::escape($dashboardUrl) ?>">Dashboard</a>
            <a href="<?= View::escape($accountUrl) ?>">Mein Zugang</a>
            <?php if($user->rights >= 4): ?><a href="<?= View::escape($membersUrl) ?>">Mitglieder</a><a href="<?= View::escape($placesUrl) ?>">Orte</a><?php endif; ?>
            <a href="<?= View::escape($departmentsUrl) ?>">Abteilungen</a>
            <a href="<?= View::escape($eventsUrl) ?>">Veranstaltungen</a>
            <a href="<?= View::escape($rightsUrl) ?>">Rechtesystem</a>
            <?php if($user->rights===5): ?><a href="<?= View::escape($adminUrl) ?>">Zugänge verwalten</a><?php endif; ?>
            <form class="vs-nav__logout" method="post" action="<?= View::escape($logoutUrl) ?>">
                <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
                <button class="vs-nav__link" type="submit">Abmelden</button>
            </form>
        </div>
    </nav>
    <?php endif; ?>
    <main id="main" class="vs-container vs-main" tabindex="-1">
        <?= $content /* Trusted rendered child view; no raw user HTML. */ ?>
    </main>
    <footer class="vs-footer"><div class="vs-container vs-footer__inner">
        <span>Vereinssystem · MVC-Neuentwicklung</span>
        <span>Originalprojekt 2012/2013 · Weiterentwicklung seit 2026</span>
    </div></footer>
</body>
</html>
