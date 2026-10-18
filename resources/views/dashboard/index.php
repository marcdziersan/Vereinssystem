<?php
/**
 * Mobile-first dashboard. No sensitive club-wide metrics for ordinary members;
 * all variables are injected by DashboardController and HTML-escaped.
 */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-hero vs-dashboard-hero" aria-labelledby="welcome-heading">
    <div class="vs-eyebrow"><span class="vs-status-dot" aria-hidden="true"></span> Mein Vereinssystem</div>
    <h1 id="welcome-heading">Willkommen, <span class="vs-hero__accent"><?= View::escape($user->firstName) ?></span></h1>
    <p class="vs-hero__description">Dein persönlicher Einstieg in die Vereinsverwaltung.</p>
    <div class="vs-actions">
        <a class="vs-button vs-button--primary" href="<?= View::escape($profileUrl) ?>">Mein Mitgliedsprofil</a>
        <?php if ($canList): ?><a class="vs-button" href="<?= View::escape($membersUrl) ?>">Mitglieder verwalten</a><?php endif; ?>
    </div>
</section>
<?php if ($canList && is_array($stats)): ?>
<section class="vs-section" aria-labelledby="statistics-title">
    <div class="vs-section__heading"><h2 id="statistics-title">Mitglieder auf einen Blick</h2><p>Aktive Mitglieder nach Mitgliedschaftsart</p></div>
    <div class="vs-grid vs-grid--3 vs-stats-grid">
        <?php foreach (['total'=>'Aktive Mitglieder','ordinary'=>'Ordentlich','supporting'=>'Fördernd','resting'=>'Ruhend'] as $code=>$label): ?>
            <div class="vs-card vs-stat"><span class="vs-stat__label"><?= View::escape($label) ?></span><strong class="vs-stat__number"><?= (int)($stats[$code]??0) ?></strong></div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<section class="vs-section" aria-labelledby="actions-title">
    <div class="vs-section__heading"><h2 id="actions-title">Schnellzugriff</h2></div>
    <div class="vs-grid vs-grid--3">
        <a class="vs-card vs-card--link" href="<?= View::escape($profileUrl) ?>"><span class="vs-card__symbol">01</span><h3>Mein Mitgliedsprofil</h3><p>Persönliche Mitgliedsdaten ansehen.</p></a>
        <?php if ($canList): ?><a class="vs-card vs-card--link" href="<?= View::escape($membersUrl) ?>"><span class="vs-card__symbol">02</span><h3>Mitglieder</h3><p>Verzeichnis durchsuchen und Profile pflegen.</p></a><?php endif; ?>
        <a class="vs-card vs-card--link" href="<?= View::escape($rightsUrl) ?>"><span class="vs-card__symbol">03</span><h3>Berechtigungen</h3><p>Rechte und Rollen transparent einsehen.</p></a>
    </div>
</section>
