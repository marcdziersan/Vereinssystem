<?php
/**
 * Accessible task form: free task title, local date/time, place and named member checkboxes.
 * Selected member IDs are always resolved and verified by the domain repository.
 */
declare(strict_types=1);
use Vereinssystem\Core\View;
$eventId = (int)$event['veranstaltungs_id'];
$value = static fn(string $field): string => View::escape($values[$field] ?? '');
$error = static fn(string $field): string => isset($errors[$field])
    ? '<span class="vs-field-error" role="alert">'.View::escape($errors[$field]).'</span>' : '';
$selectedIds = array_map('intval', $selected);
?>
<section class="vs-section vs-section--first">
    <a class="vs-back" href="<?= View::escape($backUrl) ?>">← <?= View::escape($event['veranstaltungsname']) ?></a>
    <h1><?= $task ? 'Aufgabe bearbeiten' : 'Neue Aufgabe planen' ?></h1>
    <p class="vs-muted">Lege eine Aufgabe mit Datum, Uhrzeit und Ort an und wähle die beteiligten Mitglieder aus.</p>
    <form method="post" action="<?= View::escape($saveUrl) ?>" class="vs-form">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
        <input type="hidden" name="event_id" value="<?= $eventId ?>">
        <?php if ($task): ?><input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>"><?php endif; ?>
        <fieldset class="vs-card vs-fieldset">
            <legend>Aufgabendaten</legend>
            <div class="vs-field-grid">
                <label class="vs-field vs-field--full"><span class="vs-label">Aufgabe *</span>
                    <input class="vs-input" name="title" required maxlength="160" placeholder="z. B. Einlass, Platzaufbau, Getränkeausgabe" value="<?= $value('title') ?>"><?= $error('title') ?></label>
                <label class="vs-field"><span class="vs-label">Datum und Beginn *</span>
                    <input class="vs-input" type="datetime-local" name="start" required value="<?= $value('start') ?>"><?= $error('start') ?></label>
                <label class="vs-field"><span class="vs-label">Datum und Ende *</span>
                    <input class="vs-input" type="datetime-local" name="end" required value="<?= $value('end') ?>"></label>
                <label class="vs-field vs-field--full"><span class="vs-label">Ort *</span>
                    <input class="vs-input" name="place" required maxlength="255" placeholder="z. B. Vereinsheim, Haupteingang" value="<?= $value('place') ?>"><?= $error('place') ?></label>
                <label class="vs-field vs-field--full"><span class="vs-label">Beschreibung / Hinweise</span>
                    <textarea class="vs-textarea" name="description" maxlength="5000" rows="4"><?= $value('description') ?></textarea><?= $error('description') ?></label>
            </div>
        </fieldset>
        <fieldset class="vs-card vs-fieldset"><legend>Mitglieder zuweisen</legend>
            <p class="vs-muted">Wähle ein oder mehrere Mitglieder. Ohne Auswahl bleibt die Aufgabe vorerst unbesetzt.</p>
            <label class="vs-field"><span class="vs-label">Mitglieder suchen</span>
                <input type="search" class="vs-input" data-vs-member-search placeholder="Name filtern" autocomplete="off"></label>
            <?= $error('members') ?>
            <div class="vs-member-pick" data-vs-member-list>
                <?php foreach ($members as $member): ?>
                    <label class="vs-member-pick__item" data-vs-member-name="<?= View::escape((function_exists('mb_strtolower') ? mb_strtolower($member['name'], 'UTF-8') : strtolower($member['name']))) ?>">
                        <input type="checkbox" name="members[]" value="<?= (int)$member['id'] ?>" <?= in_array((int)$member['id'], $selectedIds, true) ? 'checked' : '' ?>>
                        <span><?= View::escape($member['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (!$members): ?><p class="vs-muted">Keine aktiven Mitglieder verfügbar.</p><?php endif; ?>
        </fieldset>
        <button class="vs-button vs-button--primary" type="submit">Aufgabe und Zuordnung speichern</button>
    </form>
</section>
