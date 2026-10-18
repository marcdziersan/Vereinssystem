<?php
/**
 * Shared create/edit form; values and validation errors are explicitly mapped.
 * Access roles, login hashes and banking fields are deliberately absent.
 */
declare(strict_types=1);
use Vereinssystem\Core\View;
$val=static fn(string $key): string => View::escape((string)($values[$key]??''));
$err=static fn(string $key): string => isset($errors[$key]) ? '<span class="vs-field-error" role="alert">'.View::escape($errors[$key]).'</span>' : '';
$opt=static fn(string $key,string $value): string => (string)($values[$key]??'')===$value?' selected':'';
?>
<section class="vs-section vs-section--first">
    <a class="vs-back" href="<?= View::escape($cancelUrl) ?>">← Zurück</a>
    <div class="vs-toolbar"><div><p class="vs-kicker">Mitgliederverwaltung</p><h1><?= $member?'Mitglied bearbeiten':'Neues Mitglied' ?></h1>
        <p class="vs-muted">Pflichtfelder sind mit * markiert. Zugangsrechte werden getrennt verwaltet.</p></div></div>
    <?php if($errors): ?><div class="vs-alert vs-alert--error" role="alert">Bitte die markierten Angaben prüfen.</div><?php endif; ?>
    <form method="post" action="<?= View::escape($saveUrl) ?>" class="vs-form vs-member-form">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
        <?php if($member): ?><input type="hidden" name="id" value="<?= (int)$member->id ?>"><?php endif; ?>
        <fieldset class="vs-card vs-fieldset"><legend>Stammdaten</legend>
            <div class="vs-field-grid">
                <label class="vs-field"><span class="vs-label">Mitgliedsnummer *</span><input class="vs-input" required maxlength="25" name="number" value="<?= $val('number') ?>"><?= $err('number') ?></label>
                <label class="vs-field"><span class="vs-label">Vorname *</span><input class="vs-input" required maxlength="100" autocomplete="given-name" name="first_name" value="<?= $val('first_name') ?>"><?= $err('first_name') ?></label>
                <label class="vs-field"><span class="vs-label">Nachname *</span><input class="vs-input" required maxlength="100" autocomplete="family-name" name="last_name" value="<?= $val('last_name') ?>"><?= $err('last_name') ?></label>
                <label class="vs-field"><span class="vs-label">Geburtsdatum *</span><input class="vs-input" required type="date" name="birthday" value="<?= $val('birthday') ?>"><?= $err('birthday') ?></label>
                <label class="vs-field"><span class="vs-label">Eintritt *</span><input class="vs-input" required type="date" name="joined_at" value="<?= $val('joined_at') ?>"><?= $err('joined_at') ?></label>
                <label class="vs-field"><span class="vs-label">Geschlecht</span><select class="vs-select" name="gender"><option value="0"<?= $opt('gender','0') ?>>Keine Angabe</option><option value="1"<?= $opt('gender','1') ?>>Männlich</option><option value="2"<?= $opt('gender','2') ?>>Weiblich</option></select><?= $err('gender') ?></label>
            </div>
        </fieldset>
        <fieldset class="vs-card vs-fieldset"><legend>Kontakt und Mitgliedschaft</legend>
            <div class="vs-field-grid">
                <label class="vs-field"><span class="vs-label">E-Mail</span><input class="vs-input" name="email" type="email" autocomplete="email" maxlength="254" value="<?= $val('email') ?>"><?= $err('email') ?></label>
                <label class="vs-field"><span class="vs-label">Handy</span><input class="vs-input" name="mobile" type="tel" autocomplete="tel" maxlength="60" value="<?= $val('mobile') ?>"><?= $err('mobile') ?></label>
                <label class="vs-field"><span class="vs-label">Mitgliedschaft</span><select class="vs-select" name="membership"><option value="0"<?= $opt('membership','0') ?>>Ordentlich</option><option value="1"<?= $opt('membership','1') ?>>Fördernd</option><option value="2"<?= $opt('membership','2') ?>>Ruhend</option></select><?= $err('membership') ?></label>
                <label class="vs-field"><span class="vs-label">Abrechnung</span><select class="vs-select" name="billing"><option value="0"<?= $opt('billing','0') ?>>Bankeinzug</option><option value="1"<?= $opt('billing','1') ?>>Überweisung</option></select><?= $err('billing') ?></label>
                <label class="vs-field"><span class="vs-label">Anschrift / Ort</span><select class="vs-select" name="address_id">
                    <option value="0">Keine Anschrift ausgewählt</option>
                    <?php foreach($places as $place): ?><option value="<?= (int)$place['orts_id'] ?>" <?= $opt('address_id',(string)$place['orts_id']) ?>><?= View::escape($place['name'].' – '.$place['strasse'].' '.$place['hausnummer'].', '.$place['plz'].' '.$place['ort'].((int)$place['status']===1?'':' (inaktiv)')) ?></option><?php endforeach; ?>
                </select><?= $err('address_id') ?></label>
                <label class="vs-field"><span class="vs-label">Eltern-ID 1</span><input class="vs-input" name="parent1" type="number" min="0" value="<?= $val('parent1') ?>"><?= $err('parent1') ?></label>
                <label class="vs-field"><span class="vs-label">Eltern-ID 2</span><input class="vs-input" name="parent2" type="number" min="0" value="<?= $val('parent2') ?>"><?= $err('parent2') ?></label>
                <label class="vs-field vs-field--full"><span class="vs-label">Interne Notizen</span><textarea class="vs-textarea" name="notes" maxlength="4000" rows="4"><?= $val('notes') ?></textarea><?= $err('notes') ?></label>
            </div>
        </fieldset>
        <?php if(!$member): ?>
        <fieldset class="vs-card vs-fieldset"><legend>Erster Zugang</legend>
            <p class="vs-help">Neue Mitglieder erhalten Rechtelevel 1. Spätere Rollenänderungen erfolgen ausschließlich im Rechtesystem.</p>
            <div class="vs-field-grid">
                <label class="vs-field"><span class="vs-label">Benutzername *</span><input class="vs-input" required autocomplete="off" name="username" maxlength="100" value="<?= $val('username') ?>"><?= $err('username') ?></label>
                <label class="vs-field"><span class="vs-label">Initialpasswort (min. 12 Zeichen) *</span><input class="vs-input" required type="password" autocomplete="new-password" name="password" minlength="12" maxlength="4096"><?= $err('password') ?></label>
            </div>
        </fieldset>
        <?php endif; ?>
        <div class="vs-actions"><button class="vs-button vs-button--primary" type="submit"><?= $member?'Änderungen speichern':'Mitglied anlegen' ?></button><a class="vs-button" href="<?= View::escape($cancelUrl) ?>">Abbrechen</a></div>
    </form>
</section>
