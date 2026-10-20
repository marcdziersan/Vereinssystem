<?php
/** Server-validated, no WYSIWYG HTML injection; use newline-preserving plain text. */
declare(strict_types=1);
use Vereinssystem\Core\View;
$v=static fn(string $key):string=>View::escape($values[$key]??'');
$err=static fn(string $key):string=>isset($errors[$key])?'<span class="vs-field-error">'.View::escape($errors[$key]).'</span>':'';
?>
<section class="vs-section vs-section--first">
    <a class="vs-back" href="<?= View::escape($indexUrl) ?>">← Nachrichten</a>
    <h1><?= $post?'Nachricht bearbeiten':'Nachricht erstellen' ?></h1>
    <form method="post" action="<?= View::escape($saveUrl) ?>" class="vs-form vs-member-form">
        <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
        <?php if($post): ?><input type="hidden" name="id" value="<?= (int)$post->id ?>"><?php endif; ?>
        <fieldset class="vs-card vs-fieldset"><legend>Mitteilung</legend><div class="vs-field-grid">
            <label class="vs-field vs-field--full"><span class="vs-label">Titel *</span>
                <input name="title" class="vs-input" required maxlength="180" value="<?= $v('title') ?>"><?= $err('title') ?></label>
            <label class="vs-field vs-field--full"><span class="vs-label">Nachricht *</span>
                <textarea class="vs-textarea" name="content" rows="12" maxlength="20000" required><?= $v('content') ?></textarea><?= $err('content') ?>
                <span class="vs-muted">Klartext mit Zeilenumbrüchen. HTML wird nicht ausgeführt.</span></label>
            <label class="vs-field"><span class="vs-label">Sichtbar ab Rechtestufe</span>
                <select class="vs-select" name="minright">
                    <?php foreach([1=>'Alle Mitglieder',2=>'Moderation',3=>'Abteilungsleitung',4=>'Vorstand',5=>'Administration'] as $right=>$name): ?>
                        <?php if($right<=$userRights): ?><option value="<?= $right ?>" <?= ($values['minright']??'1')===(string)$right?'selected':'' ?>><?= View::escape($name) ?> (<?= $right ?>)</option><?php endif; ?>
                    <?php endforeach; ?>
                </select><?= $err('minright') ?></label>
        </div></fieldset>
        <button type="submit" class="vs-button vs-button--primary">Nachricht speichern</button>
    </form>
</section>
