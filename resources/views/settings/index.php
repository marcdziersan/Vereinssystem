<?php
/** Administration-only form: escaping on every value, CSRF and optimistic version. */
declare(strict_types=1);
use Vereinssystem\Core\View;
$v=$snapshot['values'];
$get=static fn(string $key):string=>View::escape((string)($v[$key]??''));
$option=static fn(string $key,string $value):string=>($v[$key]??'')===$value?' selected':'';
$check=static fn(string $key):string=>($v[$key]??'0')==='1'?' checked':'';
?>
<section class="vs-section vs-section--first">
  <div class="vs-toolbar"><div><p class="vs-kicker">Administration · Alpha 10.2</p><h1>Systemeinstellungen</h1>
    <p class="vs-muted">Vereinsdaten, Absender-/SMTP-Konfiguration und spätere Benachrichtigungen. Noch kein automatischer E-Mail-Versand.</p></div>
    <a class="vs-button" href="<?= View::escape($logsUrl) ?>">Änderungsprotokoll</a></div>
  <?php if($notice): ?><div class="vs-alert<?= ($notice['type']??'')==='error'?' vs-alert--error':'' ?>" role="status"><?= View::escape((string)($notice['message']??'')) ?></div><?php endif; ?>
  <form class="vs-form" method="post" action="<?= View::escape($saveUrl) ?>">
    <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
    <input type="hidden" name="revision" value="<?= (int)$snapshot['revision'] ?>">
    <fieldset class="vs-card vs-fieldset"><legend>Vereinsangaben</legend><div class="vs-field-grid">
      <label class="vs-field"><span class="vs-label">Vereinsname *</span><input class="vs-input" required maxlength="150" name="club_name" value="<?= $get('club_name') ?>"></label>
      <label class="vs-field"><span class="vs-label">Straße und Hausnummer</span><input class="vs-input" maxlength="180" name="club_street" value="<?= $get('club_street') ?>"></label>
      <label class="vs-field"><span class="vs-label">Postleitzahl</span><input class="vs-input" maxlength="16" name="club_postcode" value="<?= $get('club_postcode') ?>"></label>
      <label class="vs-field"><span class="vs-label">Ort</span><input class="vs-input" maxlength="120" name="club_city" value="<?= $get('club_city') ?>"></label>
      <label class="vs-field"><span class="vs-label">Vereins-E-Mail</span><input class="vs-input" type="email" maxlength="254" name="club_email" value="<?= $get('club_email') ?>"></label>
      <label class="vs-field"><span class="vs-label">Website</span><input class="vs-input" type="url" maxlength="255" name="club_website" value="<?= $get('club_website') ?>"></label>
    </div></fieldset>
    <fieldset class="vs-card vs-fieldset"><legend>E-Mail-Konfiguration</legend>
      <p class="vs-muted">Konfiguration für eine spätere Versandphase. SMTP-Passwörter werden weder hier eingegeben noch in Datenbank/Protokoll gespeichert.</p>
      <div class="vs-field-grid">
        <label class="vs-field"><span class="vs-label">Absendername</span><input class="vs-input" maxlength="150" name="mail_from_name" value="<?= $get('mail_from_name') ?>"></label>
        <label class="vs-field"><span class="vs-label">Absenderadresse</span><input class="vs-input" type="email" maxlength="254" name="mail_from_email" value="<?= $get('mail_from_email') ?>"></label>
        <label class="vs-field"><span class="vs-label">Antwortadresse</span><input class="vs-input" type="email" maxlength="254" name="mail_reply_to" value="<?= $get('mail_reply_to') ?>"></label>
        <label class="vs-field"><span class="vs-label">Geplanter Versandweg (noch nicht aktiv)</span><select class="vs-select" name="mail_transport">
          <option value="disabled"<?= $option('mail_transport','disabled') ?>>Deaktiviert</option>
          <option value="php_mail"<?= $option('mail_transport','php_mail') ?>>PHP mail() (spätere Phase)</option>
          <option value="smtp"<?= $option('mail_transport','smtp') ?>>SMTP (spätere Phase)</option>
        </select></label>
        <label class="vs-field"><span class="vs-label">SMTP-Host</span><input class="vs-input" maxlength="200" name="smtp_host" value="<?= $get('smtp_host') ?>"></label>
        <label class="vs-field"><span class="vs-label">SMTP-Port</span><input class="vs-input" type="number" min="1" max="65535" name="smtp_port" value="<?= $get('smtp_port') ?>"></label>
        <label class="vs-field"><span class="vs-label">SMTP-Verschlüsselung</span><select class="vs-select" name="smtp_encryption">
          <option value="starttls"<?= $option('smtp_encryption','starttls') ?>>STARTTLS</option>
          <option value="tls"<?= $option('smtp_encryption','tls') ?>>TLS sofort</option>
          <option value="none"<?= $option('smtp_encryption','none') ?>>Keine (nur vertrauenswürdiges Netz)</option>
        </select></label>
        <label class="vs-field"><span class="vs-label">SMTP-Benutzername (kein Passwort)</span><input class="vs-input" maxlength="180" name="smtp_username" value="<?= $get('smtp_username') ?>" autocomplete="off"></label>
      </div>
    </fieldset>
    <fieldset class="vs-card vs-fieldset"><legend>Vorgemerkte Benachrichtigungen</legend>
      <p class="vs-muted">Die folgenden Einstellungen erzeugen derzeit keine Nachrichten und lösen keine Cron-Aufgaben aus.</p>
      <div class="vs-field-grid">
        <label class="vs-field"><input type="hidden" name="notify_membership" value="0"><input type="checkbox" name="notify_membership" value="1"<?= $check('notify_membership') ?>> Beitragserinnerungen vormerken</label>
        <label class="vs-field"><input type="hidden" name="notify_events" value="0"><input type="checkbox" name="notify_events" value="1"<?= $check('notify_events') ?>> Terminbenachrichtigungen vormerken</label>
        <label class="vs-field"><input type="hidden" name="notify_birthdays" value="0"><input type="checkbox" name="notify_birthdays" value="1"<?= $check('notify_birthdays') ?>> Geburtstagsgrüße vormerken</label>
      </div>
    </fieldset>
    <div class="vs-actions"><button class="vs-button vs-button--primary" type="submit">Einstellungen speichern</button></div>
  </form>
</section>
