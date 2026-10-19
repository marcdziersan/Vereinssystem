<?php
/** Mobile event edit form; locations and contacts are selected by name. */
declare(strict_types=1);
use Vereinssystem\Core\View;
$v = static fn(string $field): string => View::escape($values[$field] ?? '');
$error = static fn(string $field): string => isset($errors[$field])
    ? '<span class="vs-field-error">'.View::escape($errors[$field]).'</span>' : '';
?>
<section class="vs-section vs-section--first">
    <a class="vs-back" href="<?= View::escape($backUrl) ?>">← Veranstaltungen</a>
    <h1><?= $event ? 'Veranstaltung bearbeiten' : 'Neue Veranstaltung' ?></h1>
    <form method="post" action="<?= View::escape($saveUrl) ?>" class="vs-form">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
        <?php if ($event): ?><input type="hidden" name="id" value="<?= (int)$event['veranstaltungs_id'] ?>"><?php endif; ?>
        <fieldset class="vs-card vs-fieldset"><legend>Termin</legend><div class="vs-field-grid">
            <label class="vs-field vs-field--full"><span class="vs-label">Name *</span>
                <input class="vs-input" name="name" required maxlength="250" value="<?= $v('name') ?>"><?= $error('name') ?></label>
            <label class="vs-field"><span class="vs-label">Beginn *</span>
                <input class="vs-input" type="datetime-local" name="start" required value="<?= $v('start') ?>"><?= $error('start') ?></label>
            <label class="vs-field"><span class="vs-label">Ende *</span>
                <input class="vs-input" type="datetime-local" name="end" required value="<?= $v('end') ?>"></label>
            <label class="vs-field"><span class="vs-label">Veranstaltungsort</span>
                <select class="vs-select" name="place"><option value="0">Kein Ort ausgewählt</option>
                    <?php foreach ($places as $place): ?><option value="<?= (int)$place['id'] ?>" <?= (string)$place['id'] === ($values['place'] ?? '0') ? 'selected' : '' ?>><?= View::escape($place['name']) ?></option><?php endforeach; ?>
                </select><?= $error('place') ?></label>
            <label class="vs-field"><span class="vs-label">Ansprechpartner</span>
                <select class="vs-select" name="contact"><option value="0">Kein Ansprechpartner</option>
                    <?php foreach ($members as $member): ?><option value="<?= (int)$member['id'] ?>" <?= (string)$member['id'] === ($values['contact'] ?? '0') ? 'selected' : '' ?>><?= View::escape($member['name']) ?></option><?php endforeach; ?>
                </select><?= $error('contact') ?></label>
            <label class="vs-field vs-field--full"><span class="vs-label">Beschreibung</span>
                <textarea class="vs-textarea" rows="5" maxlength="10000" name="description"><?= $v('description') ?></textarea><?= $error('description') ?></label>
        </div></fieldset>
        <button class="vs-button vs-button--primary" type="submit">Veranstaltung speichern</button>
    </form>
</section>
