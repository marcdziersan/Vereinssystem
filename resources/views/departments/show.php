<?php
/** Department details and member assignment (POST-only). */
declare(strict_types=1);
use Vereinssystem\Core\View;
$id=(int)$dept['abteilungs_id'];
?>
<section class="vs-section vs-section--first"><a class="vs-back" href="<?= View::escape($backUrl) ?>">← Abteilungen</a>
<div class="vs-toolbar"><div><p class="vs-kicker">Abteilung</p><h1><?= View::escape($dept['name']) ?></h1>
<p class="vs-muted"><?= View::escape(number_format((int)$dept['aktivenumlage']/100,2,',','.')) ?> € Aktivenumlage / Monat</p></div>
<?php if($canEdit&&(int)$dept['status']===1): ?><a class="vs-button" href="<?= View::escape($editUrl) ?>">Bearbeiten</a><?php endif; ?></div>
<?php if($flash): ?><div class="vs-alert" role="status"><?= View::escape($flash) ?></div><?php endif; ?>
<?php if($dept['beschreibung']!==''): ?><div class="vs-card vs-pre-line"><?= View::escape($dept['beschreibung']) ?></div><?php endif; ?>
<h2>Mitglieder in dieser Abteilung</h2>
<div class="vs-directory-grid">
<?php foreach($members as $m): ?><article class="vs-card vs-directory-card"><h3><?= View::escape($m['vorname'].' '.$m['nachname']) ?></h3>
<p class="vs-muted"><?= View::escape($m['mitgliedsnummer']) ?> · <?= (int)$m['aktiv']===1?'Aktiv':'Passiv' ?> · Seit <?= View::escape($m['beitrittdate']) ?></p>
<?php if($canAssign): ?><form action="<?= View::escape($removeUrl) ?>" method="post">
<input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>"><input type="hidden" name="department_id" value="<?= $id ?>">
<input type="hidden" name="member_id" value="<?= (int)$m['mitglied'] ?>"><button class="vs-button" type="submit">Zuordnung entfernen</button></form><?php endif; ?></article><?php endforeach; ?>
</div><?php if(!$members): ?><p class="vs-muted">Noch keine Mitglieder zugeordnet.</p><?php endif; ?>
<?php if($canAssign&&(int)$dept['status']===1): ?>
<form class="vs-card vs-form" method="post" action="<?= View::escape($assignUrl) ?>">
<h3>Mitglied zuordnen</h3><input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
<input type="hidden" name="department_id" value="<?= $id ?>"><div class="vs-field-grid">
<label class="vs-field"><span class="vs-label">Mitglieds-ID</span><input class="vs-input" type="number" required min="1" name="member_id"></label>
<label class="vs-field"><span class="vs-label">Seit</span><input class="vs-input" type="date" required name="date" value="<?= date('Y-m-d') ?>"></label>
<label class="vs-field"><span class="vs-label">Art</span><select class="vs-select" name="active"><option value="1">Aktiv</option><option value="0">Passiv</option></select></label>
</div><button class="vs-button vs-button--primary" type="submit">Zuordnen</button></form><?php endif; ?>
<?php if($canStatus): ?><form method="post" action="<?= View::escape($statusUrl) ?>">
<input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>"><input type="hidden" name="id" value="<?= $id ?>">
<input type="hidden" name="status" value="<?= (int)$dept['status']===1?2:1 ?>"><button class="vs-button" type="submit"><?= (int)$dept['status']===1?'Abteilung deaktivieren':'Abteilung reaktivieren' ?></button></form><?php endif; ?>
</section>
