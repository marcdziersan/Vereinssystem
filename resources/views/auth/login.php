<?php
/** Public login view. Never show whether an account name exists. */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-auth" aria-labelledby="login-title">
    <div class="vs-card vs-auth__panel">
        <p class="vs-eyebrow vs-eyebrow--light">Vereinssystem · geschützter Bereich</p>
        <h1 id="login-title">Willkommen zurück</h1>
        <p class="vs-muted">Mit Benutzername oder Mitgliedsnummer anmelden.</p>
        <?php if ($error !== ''): ?>
        <p role="alert" class="vs-alert vs-alert--error"><?= View::escape($error) ?></p>
        <?php endif; ?>
        <?php if (!$configured): ?>
        <p class="vs-alert" role="status">Für die Anmeldung zunächst <code>config/database.local.php</code> anhand der Vorlage einrichten. Die bestehende Vereinsdatenbank wird weiterverwendet.</p>
        <?php endif; ?>
        <form class="vs-form" method="post" action="<?= View::escape($loginUrl) ?>" autocomplete="on">
            <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
            <div class="vs-field">
                <label for="identifier" class="vs-label">Benutzername oder Mitgliedsnummer</label>
                <input id="identifier" class="vs-input" name="identifier" maxlength="150" autocomplete="username" required autofocus>
            </div>
            <div class="vs-field">
                <label for="password" class="vs-label">Passwort</label>
                <input id="password" class="vs-input" name="password" type="password" autocomplete="current-password" required>
                <label class="vs-check"><input type="checkbox" data-vs-password-toggle="password"> Passwort anzeigen</label>
            </div>
            <button class="vs-button vs-button--primary vs-button--wide" type="submit" <?= !$configured ? 'disabled' : '' ?>>Anmelden</button>
        </form>
    </div>
    <aside class="vs-auth__aside" aria-label="Entwicklungsstand">
        <span class="vs-chip vs-chip--outline">MVC 3.0.0-alpha.3</span>
        <h2>Ein System, ein Zugang.</h2>
        <p>Neuer OOP-Login auf Basis der bestehenden Vereinsdatenbank. Mitglieder, Finanzen und weitere Module werden in späteren Meilensteinen hinzugefügt.</p>
    </aside>
</section>
