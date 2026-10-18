<?php
/** Responsive list of locations; escaped data, explicit permitted actions. */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-section vs-section--first">
<div class="vs-toolbar"><div><p class="vs-kicker">Vereinsverwaltung</p><h1>Orte &amp; Anschriften</h1><p class="vs-muted">Wohnadressen und Veranstaltungsorte</p></div>
<?php if($canCreate):?><a class="vs-button vs-button--primary" href="<?= View::escape($createUrl) ?>">+ Ort anlegen</a><?php endif; ?></div>
<?php if($canStatus): ?><p><a class="vs-button" href="<?= View::escape($showInactive?$url:$url.'&inactive=1') ?>">
<?= $showInactive?'Nur aktive':'Auch inaktive anzeigen' ?></a></p><?php endif; ?>
<?php if($flash): ?><div class="vs-alert" role="status"><?= View::escape($flash) ?></div><?php endif; ?>
<div class="vs-directory-grid">
<?php foreach($items as $p): ?>
<article class="vs-card vs-directory-card"><h2><?= View::escape($p['name']) ?></h2>
<p><?= View::escape(trim($p['strasse'].' '.$p['hausnummer'])) ?></p><p><?= View::escape($p['plz'].' '.$p['ort']) ?></p>
<p class="vs-muted"><?= (int)$p['typ']===1?'Wohnanschrift':((int)$p['typ']===2?'Veranstaltungsort':'Allgemeiner Ort') ?><?= (int)$p['status']!==1?' · Inaktiv':'' ?></p>
<?php if($canEdit&&$p['status']==1): ?><a class="vs-button" href="<?= View::escape($editUrl.'&id='.(int)$p['orts_id']) ?>">Bearbeiten</a><?php endif; ?>
<?php if($canStatus): ?><form method="post" action="<?= View::escape($statusUrl) ?>"><input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$p['orts_id'] ?>"><input type="hidden" name="status" value="<?= (int)$p['status']===1?2:1 ?>"><button class="vs-button" type="submit"><?= (int)$p['status']===1?'Deaktivieren':'Reaktivieren' ?></button></form><?php endif; ?></article>
<?php endforeach; ?>
</div><?php if(!$items):?><p class="vs-card vs-empty">Noch keine Anschriften vorhanden.</p><?php endif; ?>
</section>
