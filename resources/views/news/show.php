<?php
/** All content is escaped; legacy HTML is never evaluated. */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<article class="vs-section vs-section--first vs-news-article">
    <a class="vs-back" href="<?= View::escape($indexUrl) ?>">← Alle Nachrichten</a>
    <div class="vs-card">
        <p class="vs-kicker">Vereinsnachricht</p>
        <h1><?= View::escape($post->title) ?></h1>
        <p class="vs-muted">Veröffentlicht <?= View::escape(date('d.m.Y · H:i',$post->createdAt)) ?> · <?= View::escape($author) ?></p>
        <?php if($post->changedAt>$post->createdAt+60): ?><p class="vs-muted">Zuletzt bearbeitet <?= View::escape(date('d.m.Y · H:i',$post->changedAt)) ?></p><?php endif; ?>
        <div class="vs-news-body"><?= View::escape($post->content) ?></div>
        <p class="vs-muted">Sichtbar ab Rechtestufe <?= (int)$post->minRight ?></p>
        <?php if($canEdit): ?>
            <div class="vs-news-actions">
                <a class="vs-button" href="<?= View::escape($editUrl) ?>">Bearbeiten</a>
                <form method="post" action="<?= View::escape($archiveUrl) ?>" data-vs-confirm="Nachricht wirklich archivieren?">
                    <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int)$post->id ?>">
                    <button type="submit" class="vs-button">Archivieren</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</article>
