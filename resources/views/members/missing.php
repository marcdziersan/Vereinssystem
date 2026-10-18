<?php
/** Generic 404 without member details. */
declare(strict_types=1);
use Vereinssystem\Core\View;
?>
<section class="vs-card vs-empty"><h1>Mitglied nicht gefunden</h1><p>Der Eintrag ist nicht verfügbar.</p><a class="vs-button" href="<?= View::escape($backUrl) ?>">Zur Übersicht</a></section>
