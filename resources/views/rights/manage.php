<?php
/** Admin-only role assignment. No unrelated account details appear. */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-section" aria-labelledby="roles-title">
    <p class="vs-eyebrow vs-eyebrow--light">Nur Administration</p>
    <h1 id="roles-title">Zugangsrechte verwalten</h1>
    <p class="vs-muted">Bearbeite nur die Rechtestufe. Mitgliedsdaten bleiben im späteren Mitglieder-Modul.</p>
    <?php if($flash!==''): ?><p class="vs-alert" role="status"><?= View::escape($flash) ?></p><?php endif; ?>
    <div class="vs-role-list">
    <?php foreach($members as $member): ?>
        <article class="vs-card vs-role-entry">
            <div><h2><?= View::escape($member->displayName()) ?></h2>
                <p class="vs-muted"><?= View::escape($member->username) ?> · #<?= View::escape($member->memberNumber) ?></p>
            </div>
            <?php if($member->id===$selfId): ?>
                <span class="vs-chip vs-chip--outline">Eigenes Konto · <?= View::escape($names[$member->rights] ?? 'Unbekannt') ?></span>
            <?php elseif($member->status!==1): ?>
                <span class="vs-chip vs-chip--outline">Inaktives Konto</span>
            <?php else: ?>
                <form class="vs-role-form" method="post" action="<?= View::escape($updateUrl) ?>">
                    <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$member->id ?>">
                    <label class="vs-label" for="role-<?= (int)$member->id ?>">Rechtestufe</label>
                    <select class="vs-select" id="role-<?= (int)$member->id ?>" name="level">
                        <?php foreach($names as $level=>$name): ?>
                            <option value="<?= (int)$level ?>" <?= $level===$member->rights?'selected':'' ?>><?= View::escape($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="vs-button" type="submit">Speichern</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    </div>
</section>
