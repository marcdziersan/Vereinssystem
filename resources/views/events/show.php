<?php
/** Read-only task cards for members; privileged staff receive editing actions. */
declare(strict_types=1);
use Vereinssystem\Core\View;
$eventId = (int)$event['veranstaltungs_id'];
?>
<section class="vs-section vs-section--first">
    <a class="vs-back" href="<?= View::escape($backUrl) ?>">← Veranstaltungen</a>
    <div class="vs-toolbar"><div>
        <p class="vs-kicker">Vereinsveranstaltung</p>
        <h1><?= View::escape($event['veranstaltungsname']) ?></h1>
        <p class="vs-muted"><?= View::escape(date('d.m.Y H:i', (int)$event['startzeit'])) ?> bis <?= View::escape(date('d.m.Y H:i', (int)$event['endzeit'])) ?></p>
    </div>
    <?php if ($canEdit): ?><a class="vs-button" href="<?= View::escape($editUrl) ?>">Veranstaltung bearbeiten</a><?php endif; ?>
    </div>
    <?php if ($flash): ?><div class="vs-alert" role="status"><?= View::escape($flash) ?></div><?php endif; ?>
    <div class="vs-card vs-pre-line"><?= View::escape($event['beschreibung']) ?></div>
    <div class="vs-toolbar"><h2>Aufgaben und Zuständigkeiten</h2>
        <?php if ($manager && (int)$event['status'] === 1): ?>
            <a class="vs-button vs-button--primary" href="<?= View::escape($taskCreateUrl) ?>">+ Aufgabe planen</a>
        <?php endif; ?>
    </div>
    <div class="vs-directory-grid">
        <?php foreach ($tasks as $task): ?>
            <article class="vs-card vs-directory-card">
                <h3><?= View::escape($task['titel']) ?></h3>
                <p><strong>Wann:</strong> <?= View::escape(date('d.m.Y H:i', (int)$task['startzeit'])) ?>–<?= View::escape(date('H:i', (int)$task['endzeit'])) ?></p>
                <p><strong>Ort:</strong> <?= View::escape($task['ort']) ?></p>
                <?php if ($task['beschreibung'] !== ''): ?><p class="vs-pre-line"><?= View::escape($task['beschreibung']) ?></p><?php endif; ?>
                <strong>Zugewiesene Mitglieder</strong>
                <?php if ($task['members']): ?>
                    <ul><?php foreach ($task['members'] as $member): ?>
                        <li><?= View::escape($member['name']) ?></li>
                    <?php endforeach; ?></ul>
                <?php else: ?><p class="vs-muted">Noch niemand zugeteilt.</p><?php endif; ?>
                <?php if ($manager && (int)$event['status'] === 1): ?>
                    <div class="vs-toolbar">
                        <a class="vs-button" href="<?= View::escape($taskEditUrl.'&event_id='.$eventId.'&task_id='.(int)$task['id']) ?>">Bearbeiten / Mitglieder auswählen</a>
                        <form method="post" action="<?= View::escape($taskArchiveUrl) ?>">
                            <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
                            <input type="hidden" name="event_id" value="<?= $eventId ?>">
                            <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                            <button class="vs-button" type="submit">Archivieren</button>
                        </form>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$tasks): ?><div class="vs-card vs-empty">Für diese Veranstaltung sind noch keine Aufgaben angelegt.</div><?php endif; ?>
    <?php if ($canStatus): ?><form method="post" action="<?= View::escape($statusUrl) ?>">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
        <input type="hidden" name="id" value="<?= $eventId ?>">
        <input type="hidden" name="status" value="<?= (int)$event['status'] === 1 ? 2 : 1 ?>">
        <button class="vs-button" type="submit"><?= (int)$event['status'] === 1 ? 'Veranstaltung deaktivieren' : 'Veranstaltung reaktivieren' ?></button>
    </form><?php endif; ?>
</section>
