<?php
/** Responsive member directory with GET search and bounded pagination. */
declare(strict_types=1);
use Vereinssystem\Core\View;
/** Pagination URLs are constructed only from a known route and encoded values. */
$link = static fn(int $page): string => $listUrl . '&q=' . rawurlencode($q)
    . '&filter=' . rawurlencode($filter) . '&page=' . $page;
?>
<section class="vs-section vs-section--first" aria-labelledby="members-title">
    <div class="vs-toolbar">
        <div><p class="vs-kicker">Verwaltung</p><h1 id="members-title">Mitglieder</h1><p class="vs-muted"><?= (int)$result['total'] ?> Treffer</p></div>
        <?php if ($mayAdd): ?><a class="vs-button vs-button--primary" href="<?= View::escape($newUrl) ?>">+ Mitglied anlegen</a><?php endif; ?>
    </div>
    <?php if ($flash !== ''): ?><div class="vs-alert" role="status"><?= View::escape($flash) ?></div><?php endif; ?>
    <form class="vs-searchbar" method="get" action="<?= View::escape($listUrl) ?>" role="search">
        <input type="hidden" name="r" value="/members">
        <label class="vs-field vs-field--grow"><span class="vs-label">Mitglieder suchen</span>
            <input class="vs-input" name="q" type="search" maxlength="100" placeholder="Name, Nummer oder Benutzername" value="<?= View::escape($q) ?>">
        </label>
        <?php if ($isAdmin): ?>
        <label class="vs-field"><span class="vs-label">Status</span>
            <select class="vs-select" name="filter">
                <option value="active" <?= $filter==='active'?'selected':'' ?>>Aktiv</option>
                <option value="inactive" <?= $filter==='inactive'?'selected':'' ?>>Inaktiv</option>
            </select>
        </label>
        <?php endif; ?>
        <button class="vs-button vs-button--primary" type="submit">Suchen</button>
    </form>
    <?php if ($result['items'] === []): ?><div class="vs-card vs-empty">Keine Mitglieder mit diesen Kriterien gefunden.</div><?php endif; ?>
    <div class="vs-member-grid">
        <?php foreach ($result['items'] as $member): ?>
            <article class="vs-card vs-member-card">
                <span class="vs-avatar" aria-hidden="true"><?= View::escape((preg_match('/^./u',$member->firstName,$a)?$a[0]:'?').(preg_match('/^./u',$member->lastName,$b)?$b[0]:'?')) ?></span>
                <div class="vs-member-card__content">
                    <h2><?= View::escape($member->fullName()) ?></h2>
                    <p class="vs-muted">Mitgliedsnummer <?= View::escape($member->number) ?></p>
                    <div class="vs-member-card__meta">
                        <span class="vs-chip vs-chip--outline"><?= $member->status===1?'Aktiv':'Inaktiv' ?></span>
                        <?php if ($member->email !== ''): ?><span class="vs-member-card__email"><?= View::escape($member->email) ?></span><?php endif; ?>
                    </div>
                </div>
                <a class="vs-button" href="<?= View::escape($viewUrl.'&id='.$member->id) ?>" aria-label="Profil von <?= View::escape($member->fullName()) ?> ansehen">Profil</a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($result['pages']>1): ?>
    <nav class="vs-pager" aria-label="Mitgliederseiten">
        <?php if ($result['page']>1): ?><a class="vs-button" href="<?= View::escape($link($result['page']-1)) ?>">← Vorherige</a><?php endif; ?>
        <span>Seite <?= (int)$result['page'] ?> von <?= (int)$result['pages'] ?></span>
        <?php if ($result['page']<$result['pages']): ?><a class="vs-button" href="<?= View::escape($link($result['page']+1)) ?>">Nächste →</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</section>
