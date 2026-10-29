<?php
/** Historical configuration audit log, admin-only (and escaped). */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-section vs-section--first">
  <a class="vs-back" href="<?= View::escape($backUrl) ?>">← Systemeinstellungen</a>
  <h1>Änderungsprotokoll</h1>
  <p class="vs-muted">Protokolliert werden nur Änderungen an freigegebenen, nicht geheimen Einstellungsfeldern. Keine SMTP-Passwörter.</p>
  <?php if(!$history): ?><p class="vs-card">Noch keine Änderungen protokolliert.</p><?php endif; ?>
  <?php foreach($history as $entry): ?>
    <article class="vs-card vs-section">
      <h2>Version <?= (int)$entry['revision'] ?> · <?= View::escape($entry['created_at']) ?></h2>
      <p class="vs-muted">Änderung durch Mitglied #<?= (int)$entry['actor_id'] ?></p>
      <ul>
      <?php foreach($entry['changes'] as $field=>$change): ?>
        <li><strong><?= View::escape($field) ?></strong>: <?= View::escape((string)$change['before']) ?> → <?= View::escape((string)$change['after']) ?></li>
      <?php endforeach; ?>
      </ul>
    </article>
  <?php endforeach; ?>
  <div class="vs-actions">
    <?php if($pageNumber>1): ?><a class="vs-button" href="<?= View::escape($prevUrl) ?>">Vorherige Seite</a><?php endif; ?>
    <?php if(count($history)===30): ?><a class="vs-button" href="<?= View::escape($nextUrl) ?>">Weitere Einträge</a><?php endif; ?>
  </div>
</section>
