<?php
/**
 * Vereinssystem 2.3.0 – Modul: kasse protokoll.
 * Pfad: kasse_protokoll.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/** Lesegeschützte Änderungs-/Stornohistorie ohne Schreibaktionen. */
require_once __DIR__.'/inc/connect.inc.php';
require_once __DIR__.'/inc/checkuser.inc.php';
require_once __DIR__.'/inc/cash.php';
vk_require($user,'kasse.pruefung');
$items=vf_all('SELECT a.*,m.vorname,m.nachname FROM vs_audit a LEFT JOIN mitglieder m ON m.mitglieder_id=a.akteur ORDER BY a.id DESC LIMIT 300');
$title='Kassenprüfprotokoll';require __DIR__.'/inc/head.inc.php';
?>
<div id="content"><section class="vf vk"><p><a href="kasse.php">← Kasse</a></p><h1>Nachvollziehbare Änderungen</h1><p>Letzte 300 Aktionen: Kontoanlage, Geldbewegungen, Beitragstarife, Rechte und Stornos. Die Protokolldatensätze werden bei normalen Bearbeitungsaktionen nicht gelöscht.</p><div class="scroll"><table><thead><tr><th>Zeit</th><th>Akteur</th><th>Aktion</th><th>Objekt</th><th>Details</th></tr></thead><tbody>
<?php foreach($items as $item):?><tr><td><?=vf_escape($item['erstellt_am'])?></td><td><?=vf_escape(trim(($item['vorname']??'').' '.($item['nachname']??'')))?> (#<?=(int)$item['akteur']?>)</td><td><?=vf_escape($item['aktion'])?></td><td><?=vf_escape($item['objekt'])?> #<?=(int)$item['objekt_id']?></td><td><pre><?=vf_escape($item['details'])?></pre></td></tr><?php endforeach;?></tbody></table></div></section></div><?php require __DIR__.'/inc/footer.inc.php'; ?>