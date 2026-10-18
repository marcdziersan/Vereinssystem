<?php
/** Öffentliche Anmeldung – Mobile-first-Ansicht mit unverändertem Login-Endpunkt. */
require_once __DIR__ . '/inc/connect.inc.php';
vs_session_start();
if (empty($_SESSION['vs_login_token'])) {
    $_SESSION['vs_login_token'] = bin2hex(random_bytes(32));
}
if ((int) ($settings['usecaptchalogin'] ?? 0) === 1) {
    $_SESSION['vs_captcha'] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
}
$title = 'Anmelden';
require_once __DIR__ . '/inc/head.inc.php';

$loginAlert = null;
$loginMessage = '';
if (isset($_GET['fail'])) {
    $loginAlert = 'error';
    $loginMessage = match ((string) $_GET['fail']) {
        'wait' => 'Zu viele Versuche. Bitte versuchen Sie es später erneut.',
        'captcha' => 'Der eingegebene Sicherheitscode war nicht korrekt.',
        'cookie' => 'Cookies sind für die Anmeldung erforderlich. Bitte aktivieren Sie diese im Browser.',
        default => 'Anmeldung fehlgeschlagen. Bitte prüfen Sie Benutzernamen und Passwort.',
    };
} elseif (isset($_GET['loggedout'])) {
    $loginAlert = 'success';
    $loginMessage = 'Sie haben sich erfolgreich abgemeldet.';
} elseif (isset($_GET['old'])) {
    $loginAlert = 'information';
    $loginMessage = 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.';
} elseif (isset($_GET['forbidden'])) {
    $loginAlert = 'error';
    $loginMessage = 'Dieses Benutzerkonto ist gesperrt oder noch nicht freigeschaltet. Bitte wenden Sie sich an die Vereinsverwaltung.';
}
?>
<main id="content">
  <div class="vs-login-layout">
    <section class="vs-login-panel" aria-labelledby="vs-login-title">
      <p class="vs-login-kicker">SICHERER MITGLIEDERBEREICH</p>
      <h1 class="vs-login-title" id="vs-login-title">Willkommen zurück.</h1>
      <p class="vs-form-note">Melden Sie sich an, um Ihr Vereinsprofil und Ihre Aufgaben zu verwalten.</p>
      <?php if ($loginAlert !== null): ?>
        <div class="message <?= vui_escape($loginAlert) ?>" role="status" aria-live="polite">
          <p><?= vui_escape($loginMessage) ?></p>
        </div>
      <?php endif; ?>
      <form id="loginform" class="formular" method="post" action="login_process.php" accept-charset="utf-8">
        <input type="hidden" name="vs_login_token" value="<?= vui_escape($_SESSION['vs_login_token']) ?>">
        <?php if (isset($_GET['after'])): ?>
          <input type="hidden" name="after" value="<?= vui_escape($_GET['after']) ?>">
        <?php endif; ?>
        <p>
          <label for="username">Benutzername oder Mitgliedsnummer</label>
          <input type="text" name="username" id="username" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="Benutzername eingeben" required autofocus>
        </p>
        <p>
          <label for="psword">Passwort</label>
          <span class="vs-password-field">
            <input type="password" name="psword" id="psword" autocomplete="current-password" placeholder="Passwort eingeben" required>
            <button type="button" class="vs-password-toggle" aria-controls="psword" aria-pressed="false" aria-label="Passwort anzeigen" title="Passwort anzeigen"><?= vui_icon('eye',19) ?></button>
          </span>
        </p>
        <?php if ((int) ($settings['usecaptchalogin'] ?? 0) === 1): ?>
          <p><img src="captcha.php" alt="Bild mit Sicherheitscode" width="180" height="58"></p>
          <p><label for="captcha">Sicherheitscode aus dem Bild</label>
          <input type="text" name="captcha" id="captcha" maxlength="4" autocomplete="off" inputmode="text" required></p>
        <?php endif; ?>
        <p><button class="button" type="submit" name="Login" value="Login">Anmelden <?= vui_icon('arrow',18) ?></button></p>
      </form>
      <p class="vs-form-note">Der Zugang ist ausschließlich für freigeschaltete Vereinsmitglieder vorgesehen.</p>
    </section>
    <aside class="vs-login-intro" aria-label="Über das Vereinssystem">
      <p class="vs-login-kicker" style="color:#79dfaf">ALLES IM VEREIN. EIN ORT.</p>
      <h2>Mehr Überblick.<br>Weniger Verwaltungsaufwand.</h2>
      <p>Ob Mitgliedsdaten, Veranstaltung oder Vereinsfinanzen – Ihre Aufgaben bleiben übersichtlich und an einem gemeinsamen Ort.</p>
      <ul class="vs-login-points">
        <li><?= vui_icon('check',19) ?> Mitglieder und Abteilungen</li>
        <li><?= vui_icon('check',19) ?> Veranstaltungen und Helferdienste</li>
        <li><?= vui_icon('check',19) ?> Beiträge, Zahlungen und Kasse</li>
      </ul>
    </aside>
  </div>
</main>
<?php require_once __DIR__ . '/inc/footer.inc.php'; ?>