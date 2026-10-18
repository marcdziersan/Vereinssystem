<?php
/** First-run installer – values escape at every HTML output boundary. */
declare(strict_types=1);
use Vereinssystem\Core\View;
$v=static fn(string $key,string $default=''):string=>View::escape((string)($values[$key]??$default));
?>
<section class="vs-section vs-section--first">
    <p class="vs-kicker">MVC 3.0.0-alpha.4</p><h1>Vereinssystem einrichten</h1>
    <p class="vs-muted">Einmalige Installation mit MySQL/MariaDB. Existierende Mitgliederdaten werden nicht gelöscht.</p>
    <?php if($error!==''): ?><div class="vs-alert vs-alert--error" role="alert"><?= View::escape($error) ?></div><?php endif; ?>
    <form class="vs-form" method="post" action="<?= View::escape($installUrl) ?>" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
        <fieldset class="vs-card vs-fieldset"><legend>Datenbank</legend><div class="vs-field-grid">
            <label class="vs-field"><span class="vs-label">Host</span><input class="vs-input" name="host" required value="<?= $v('host','localhost') ?>"></label>
            <label class="vs-field"><span class="vs-label">Port</span><input class="vs-input" name="port" type="number" required min="1" max="65535" value="<?= $v('port','3306') ?>"></label>
            <label class="vs-field"><span class="vs-label">Datenbankname</span><input class="vs-input" name="database" required value="<?= $v('database') ?>"></label>
            <label class="vs-field"><span class="vs-label">Datenbankbenutzer</span><input class="vs-input" name="username" required value="<?= $v('username') ?>"></label>
            <label class="vs-field"><span class="vs-label">Datenbankpasswort</span><input class="vs-input" type="password" name="db_password" autocomplete="new-password"></label>
        </div></fieldset>
        <fieldset class="vs-card vs-fieldset"><legend>Erster Administrator</legend>
            <p class="vs-help">Das Konto wird nur angelegt, wenn die Mitgliedertabelle noch leer ist.</p>
            <div class="vs-field-grid">
                <label class="vs-field"><span class="vs-label">Vorname</span><input class="vs-input" name="first" required value="<?= $v('first') ?>"></label>
                <label class="vs-field"><span class="vs-label">Nachname</span><input class="vs-input" name="last" required value="<?= $v('last') ?>"></label>
                <label class="vs-field"><span class="vs-label">Benutzername</span><input class="vs-input" name="login" required minlength="3" value="<?= $v('login') ?>"></label>
                <label class="vs-field"><span class="vs-label">E-Mail-Adresse</span><input class="vs-input" type="email" name="email" required value="<?= $v('email') ?>"></label>
                <label class="vs-field"><span class="vs-label">Geburtsdatum</span><input class="vs-input" type="date" name="birthday" required value="<?= $v('birthday') ?>"></label>
                <label class="vs-field"><span class="vs-label">Administratorpasswort</span><input class="vs-input" name="admin_password" type="password" required minlength="12" autocomplete="new-password"></label>
                <label class="vs-field"><span class="vs-label">Passwort wiederholen</span><input class="vs-input" name="confirm_password" type="password" required minlength="12" autocomplete="new-password"></label>
            </div>
        </fieldset>
        <button class="vs-button vs-button--primary" type="submit">Vereinssystem installieren</button>
    </form>
</section>
