<?php
/** Authenticated landing view: account details, no member management. */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-section" aria-labelledby="account-title">
    <p class="vs-eyebrow vs-eyebrow--light">Angemeldeter Zugang</p>
    <h1 id="account-title">Hallo, <?= View::escape($user->firstName) ?>!</h1>
    <p class="vs-muted">Der neue MVC-Login ist aktiv. Deine Berechtigungen werden bei jeder Anfrage erneut aus der Datenbank gelesen.</p>
    <div class="vs-grid vs-grid--3 vs-account-grid">
        <article class="vs-card"><p class="vs-mini-title">Benutzername</p><h2><?= View::escape($user->username) ?></h2></article>
        <article class="vs-card"><p class="vs-mini-title">Mitgliedsnummer</p><h2><?= View::escape($user->memberNumber) ?></h2></article>
        <article class="vs-card"><p class="vs-mini-title">Rolle</p><h2><?= View::escape($role) ?></h2><p>Rechtestufe <?= (int)$user->rights ?> von 5</p></article>
    </div>
</section>
<section class="vs-section"><a class="vs-button vs-button--primary" href="<?= View::escape($settingsUrl) ?>">Zugangsdaten und Passwort verwalten</a></section>
