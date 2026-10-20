<?php
/** Safe bulletin index. Search is GET-only and never changes stored content. */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-section vs-section--first">
    <div class="vs-toolbar">
        <div><p class="vs-kicker">Vereinsleben</p><h1>Nachrichten</h1><p class="vs-muted">Mitteilungen für Mitglieder und Vereinsführung.</p></div>
        <div class="vs-news-actions">
        <?php if($canCreate): ?><a class="vs-button vs-button--primary" href="<?= View::escape($createUrl) ?>">+ Nachricht</a><?php endif; ?>
        <?php if($isAdmin): ?><a class="vs-button" href="<?= View::escape($archiveUrl) ?>">Archiv</a><?php endif; ?>
        </div>
    </div>
    <?php if($flash): ?><div class="vs-alert" role="status"><?= View::escape($flash) ?></div><?php endif; ?>
    <form class="vs-searchbar" action="<?= View::escape($indexUrl) ?>" method="get">
        <label class="vs-field vs-field--grow"><span class="vs-label">Nachrichten durchsuchen</span>
            <input class="vs-input" type="search" name="q" maxlength="100" placeholder="Titel oder Inhalt" value="<?= View::escape($search) ?>"></label>
        <button class="vs-button" type="submit">Suchen</button>
    </form>
    <p class="vs-muted"><?= (int)$total ?> sichtbare <?= (int)$total===1?'Nachricht':'Nachrichten' ?></p>
    <div class="vs-directory-grid vs-news-grid">
        <?php foreach($posts as $post): ?>
            <article class="vs-card vs-directory-card">
                <p class="vs-kicker"><?= View::escape(date('d.m.Y · H:i',(int)$post['created'])) ?></p>
                <h2><?= View::escape($post['title']) ?></h2>
                <p class="vs-news-preview"><?= View::escape($post['content']) ?></p>
                <p class="vs-muted">Von <?= View::escape(trim($post['author_first'].' '.$post['author_last'])?:'Vereinsverwaltung') ?></p>
                <a class="vs-button" href="<?= View::escape($showUrl.'&id='.(int)$post['news_id']) ?>">Lesen</a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if(!$posts): ?><div class="vs-card vs-empty">Keine passenden Mitteilungen vorhanden.</div><?php endif; ?>
    <?php if($pageCount>1): ?>
        <nav class="vs-pager" aria-label="Nachrichtenseiten">
            <?php if($pageNumber>1): ?><a class="vs-button" href="<?= View::escape($indexUrl.'&q='.rawurlencode($search).'&page='.($pageNumber-1)) ?>">← Zurück</a><?php endif; ?>
            <span>Seite <?= (int)$pageNumber ?> von <?= (int)$pageCount ?></span>
            <?php if($pageNumber<$pageCount): ?><a class="vs-button" href="<?= View::escape($indexUrl.'&q='.rawurlencode($search).'&page='.($pageNumber+1)) ?>">Weiter →</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
