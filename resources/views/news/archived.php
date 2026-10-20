<?php
/** Restricted archive: no content published while archived. */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-section vs-section--first">
    <a class="vs-back" href="<?= View::escape($indexUrl) ?>">← Nachrichten</a>
    <h1>Archivierte Nachrichten</h1>
    <p class="vs-muted">Diese Nachrichten sind für Mitglieder nicht sichtbar. Wiederherstellung ist nur durch die Administration möglich.</p>
    <?php if($flash): ?><div class="vs-alert" role="status"><?= View::escape($flash) ?></div><?php endif; ?>
    <div class="vs-directory-grid">
        <?php foreach($posts as $post): ?>
            <article class="vs-card vs-directory-card">
                <p class="vs-kicker">Zuletzt geändert <?= View::escape(date('d.m.Y · H:i',$post->changedAt)) ?></p>
                <h2><?= View::escape($post->title) ?></h2>
                <p class="vs-muted">Rechtestufe <?= (int)$post->minRight ?> · Ersteller-ID <?= (int)$post->authorId ?></p>
                <form method="post" action="<?= View::escape($restoreUrl) ?>">
                    <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int)$post->id ?>">
                    <button type="submit" class="vs-button">Wiederherstellen</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if(!$posts): ?><div class="vs-card vs-empty">Das Nachrichtenarchiv ist leer.</div><?php endif; ?>
</section>
