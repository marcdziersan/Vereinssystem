<?php
/** Mobile-first cards for departmental overview. */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-section vs-section--first"><div class="vs-toolbar"><div><p class="vs-kicker">Vereinsleben</p><h1>Abteilungen</h1><p class="vs-muted">Gruppen, Umlagen und Zugehörigkeiten</p></div>
<?php if($canCreate): ?><a class="vs-button vs-button--primary" href="<?= View::escape($createUrl) ?>">+ Abteilung</a><?php endif; ?></div>
<?php if($isAdmin): ?><p><a class="vs-button" href="<?= View::escape($showInactive?$listUrl:$listUrl.'&inactive=1') ?>"><?= $showInactive?'Nur aktive':'Auch inaktive anzeigen' ?></a></p><?php endif; ?>
<?php if($flash): ?><div class="vs-alert" role="status"><?= View::escape($flash) ?></div><?php endif; ?>
<div class="vs-directory-grid">
<?php foreach($items as $d): ?><article class="vs-card vs-directory-card">
<h2><?= View::escape($d['name']) ?></h2><p class="vs-muted"><?= (int)$d['member_count'] ?> Zugehörigkeiten</p>
<p>Aktivenumlage: <?= View::escape(number_format((int)$d['aktivenumlage']/100,2,',','.')) ?> € / Monat</p>
<?php if((int)$d['status']!==1): ?><p class="vs-muted">Inaktiv</p><?php endif; ?>
<a class="vs-button" href="<?= View::escape($showUrl.'&id='.(int)$d['abteilungs_id']) ?>">Anzeigen</a>
</article><?php endforeach; ?>
</div><?php if(!$items): ?><div class="vs-card vs-empty">Keine Abteilungen angelegt.</div><?php endif; ?></section>
