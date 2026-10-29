<?php
/** Account self-service; only whitelisted fields are editable. */
declare(strict_types=1);
use Vereinssystem\Core\View;
$escape = static fn(mixed $value): string => View::escape((string)$value);
?>
<section class="vs-section vs-section--first" aria-labelledby="self-service-heading">
    <a class="vs-back" href="<?= $escape($accountUrl) ?>">← Mein Zugang</a>
    <h1 id="self-service-heading">Zugangsdaten und Sicherheit</h1>
    <p class="vs-muted">Diese Änderungen betreffen nur deinen eigenen Zugang. Mitgliedschaft, Rechtestufe und Beitragskonto bleiben unverändert.</p>
    <?php if ($notice !== null): ?>
        <p role="<?= ($notice['type'] ?? '') === 'error' ? 'alert' : 'status' ?>" class="vs-alert <?= ($notice['type'] ?? '') === 'error' ? 'vs-alert--error' : '' ?>"><?= $escape($notice['message'] ?? '') ?></p>
    <?php endif; ?>
    <?php if ($profile === null): ?>
        <p class="vs-alert vs-alert--error" role="alert">Dein Profil ist derzeit nicht verfügbar.</p>
    <?php else: ?>
        <form class="vs-form vs-card" method="post" action="<?= $escape($profileUrl) ?>">
            <h2>Profil und Kontaktdaten</h2>
            <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
            <div class="vs-field-grid">
                <label class="vs-field"><span class="vs-label">Benutzername *</span><input class="vs-input" type="text" name="username" autocomplete="username" required minlength="3" maxlength="100" value="<?= $escape($profile['username']) ?>"></label>
                <label class="vs-field"><span class="vs-label">E-Mail</span><input class="vs-input" type="email" name="email" autocomplete="email" maxlength="254" value="<?= $escape($profile['email']) ?>"></label>
                <label class="vs-field"><span class="vs-label">Mobilnummer</span><input class="vs-input" type="tel" name="mobile" autocomplete="tel" maxlength="60" value="<?= $escape($profile['mobile']) ?>"></label>
                <label class="vs-field"><span class="vs-label">Aktuelles Passwort *</span><input class="vs-input" type="password" name="current_password" autocomplete="current-password" required></label>
            </div>
            <div class="vs-actions"><button class="vs-button vs-button--primary" type="submit">Profil speichern</button></div>
        </form>
        <form class="vs-form vs-card" method="post" action="<?= $escape($passwordUrl) ?>">
            <h2>Passwort ändern</h2>
            <p class="vs-help">Mindestens 12 Zeichen. Nach der Änderung wird die aktuelle Sitzung erneuert.</p>
            <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
            <div class="vs-field-grid">
                <label class="vs-field"><span class="vs-label">Aktuelles Passwort *</span><input class="vs-input" type="password" name="current_password" autocomplete="current-password" required></label>
                <label class="vs-field"><span class="vs-label">Neues Passwort *</span><input class="vs-input" type="password" name="new_password" autocomplete="new-password" minlength="12" maxlength="4096" required></label>
                <label class="vs-field"><span class="vs-label">Neues Passwort wiederholen *</span><input class="vs-input" type="password" name="confirm_password" autocomplete="new-password" minlength="12" maxlength="4096" required></label>
            </div>
            <div class="vs-actions"><button class="vs-button vs-button--primary" type="submit">Passwort ändern</button></div>
        </form>
    <?php endif; ?>
</section>
