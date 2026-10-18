<?php
/** Readable rights overview. Unimplemented modules are deliberately not shown. */
declare(strict_types=1);
use Vereinssystem\Core\View;
use Vereinssystem\Auth\User;
?>
<section class="vs-section" aria-labelledby="rights-title">
    <p class="vs-eyebrow vs-eyebrow--light">Zentrale Berechtigungen</p>
    <h1 id="rights-title">Rechtesystem</h1>
    <p class="vs-muted">Die Rollenbezeichnungen stammen aus dem bisherigen Vereinssystem. Freigaben werden zentral im OOP-Kern geprüft.</p>
    <div class="vs-table-wrap" role="region" aria-label="Rechteübersicht" tabindex="0">
        <table class="vs-table"><thead><tr><th scope="col">Funktion</th>
        <?php foreach($names as $number=>$name): if($number===0)continue; ?>
            <th scope="col"><?= View::escape($name) ?></th>
        <?php endforeach; ?></tr></thead><tbody>
        <?php foreach($permissions as $capability=>$min): ?>
            <tr><th scope="row"><?= View::escape($capability) ?></th>
            <?php for($level=1;$level<=5;$level++): ?>
                <td aria-label="<?= $level >= $min ? 'Erlaubt':'Nicht erlaubt' ?>"><?= $level >= $min ? '✓':'–' ?></td>
            <?php endfor; ?></tr>
        <?php endforeach; ?></tbody></table>
    </div>
    <p class="vs-help">Weitere Fachrechte werden erst bei der Umsetzung der entsprechenden Module aktiviert. Rechtelevel 0 sperrt die Anmeldung.</p>
</section>
