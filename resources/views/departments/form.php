<?php
/** Department editor; exact Euro-to-cent conversion happens in the service. */
declare(strict_types=1);
use Vereinssystem\Core\View;
$v=static fn(string $key):string=>View::escape((string)($values[$key]??''));
$err=static fn(string $key):string=>isset($errors[$key])?'<span class="vs-field-error">'.View::escape($errors[$key]).'</span>':'';
$amount=isset($values['aktivenumlage'])?($dept&&!$errors?number_format((int)$values['aktivenumlage']/100,2,',',''):(string)$values['aktivenumlage']):'0,00';
?>
<section class="vs-section vs-section--first"><a class="vs-back" href="<?= View::escape($backUrl) ?>">← Abteilungen</a>
<h1><?= $dept?'Abteilung bearbeiten':'Neue Abteilung' ?></h1><form class="vs-form" method="post" action="<?= View::escape($saveUrl) ?>">
<input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>"><?php if($dept): ?><input type="hidden" name="id" value="<?= (int)$dept['abteilungs_id'] ?>"><?php endif; ?>
<fieldset class="vs-card vs-fieldset"><legend>Abteilungsdaten</legend><div class="vs-field-grid">
<label class="vs-field"><span class="vs-label">Abteilungsname *</span><input class="vs-input" name="name" required maxlength="160" value="<?= $v('name') ?>"><?= $err('name') ?></label>
<label class="vs-field"><span class="vs-label">Homepage (optional)</span><input class="vs-input" type="url" name="homepage" maxlength="500" value="<?= $v('homepage') ?>"><?= $err('homepage') ?></label>
<label class="vs-field"><span class="vs-label">Abteilungsleiter (Mitglieds-ID)</span><input class="vs-input" type="number" min="0" name="abteilungsleiter" value="<?= $v('abteilungsleiter')!==''?$v('abteilungsleiter'):'0' ?>"><?= $err('abteilungsleiter') ?></label>
<label class="vs-field"><span class="vs-label">Aktivenumlage pro Monat (€)</span><input class="vs-input" inputmode="decimal" name="aktivenumlage" value="<?= View::escape($amount) ?>"><?= $err('aktivenumlage') ?></label>
<label class="vs-field vs-field--full"><span class="vs-label">Beschreibung</span><textarea class="vs-textarea" name="beschreibung" rows="5" maxlength="5000"><?= $v('beschreibung') ?></textarea><?= $err('beschreibung') ?></label>
</div></fieldset><button type="submit" class="vs-button vs-button--primary">Abteilung speichern</button></form></section>
