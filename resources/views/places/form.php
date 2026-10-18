<?php
/** Place form uses explicit column mapping and the shared UI components. */
declare(strict_types=1);
use Vereinssystem\Core\View;
$v=static fn(string $k):string=>View::escape((string)($values[$k]??''));
$err=static fn(string $k):string=>isset($errors[$k])?'<span class="vs-field-error">'.View::escape($errors[$k]).'</span>':'';
?>
<section class="vs-section vs-section--first">
<a class="vs-back" href="<?= View::escape($cancelUrl) ?>">← Orte</a>
<h1><?= $place?'Ort bearbeiten':'Neuer Ort' ?></h1>
<form class="vs-form" method="post" action="<?= View::escape($saveUrl) ?>">
<input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
<?php if($place):?><input type="hidden" name="id" value="<?= (int)$place['orts_id'] ?>"><?php endif; ?>
<fieldset class="vs-card vs-fieldset"><legend>Anschrift</legend><div class="vs-field-grid">
<?php foreach(['name'=>'Bezeichnung *','strasse'=>'Straße *','hausnummer'=>'Hausnummer','plz'=>'Postleitzahl *','ort'=>'Ort *','telefon'=>'Telefon'] as $key=>$label): ?>
<label class="vs-field"><span class="vs-label"><?= View::escape($label) ?></span><input class="vs-input" name="<?= $key ?>" value="<?= $v($key) ?>" <?= in_array($key,['name','strasse','plz','ort'],true)?'required':'' ?>" maxlength="<?= $key==='plz'?5:($key==='hausnummer'?5:250) ?>"><?= $err($key) ?></label>
<?php endforeach; ?>
<label class="vs-field"><span class="vs-label">Art</span><select class="vs-select" name="typ">
<?php foreach([0=>'Allgemein',1=>'Wohnanschrift',2=>'Veranstaltungsort'] as $id=>$label): ?><option value="<?= $id ?>" <?= (string)($values['typ']??'0')===(string)$id?'selected':'' ?>><?= View::escape($label) ?></option><?php endforeach; ?>
</select><?= $err('typ') ?></label>
</div></fieldset><div class="vs-actions"><button class="vs-button vs-button--primary" type="submit">Speichern</button></div></form></section>
