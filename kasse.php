<?php
/**
 * Vereinssystem 2.3.0 – Modul: kasse.
 * Pfad: kasse.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/** Responsive Kassenoberfläche: Konten, Salden, Buchungen, Storno und Exporte. */
require_once __DIR__ . '/inc/connect.inc.php';
require_once __DIR__ . '/inc/checkuser.inc.php';
require_once __DIR__ . '/inc/cash.php';
vk_require($user,'kasse.ansehen');
try {$year=vf_year((string)($_GET['jahr']??date('Y')));}catch(DomainException){$year=(int)date('Y');}
$selected=max(0,(int)($_GET['konto']??0));
$accounts=vk_accounts(true);
if ($selected && !in_array($selected,array_map(static fn($x)=>(int)$x['id'],$accounts),true)) $selected=0;
$balances=vk_balances($year,$selected?:null);
$entries=vk_entries($year,$selected?:null);
$flash=$_SESSION['vk_flash']??null;unset($_SESSION['vk_flash']);
$_SESSION['vk_nonce']=bin2hex(random_bytes(20));
$title='Kassenverwaltung';
require __DIR__ . '/inc/head.inc.php';
function kh(mixed $x): string {return vf_escape($x);}
function ktoken(): string {return '<input type="hidden" name="vs_csrf" value="'.kh($_SESSION['vs_csrf']).'"><input type="hidden" name="vk_nonce" value="'.kh($_SESSION['vk_nonce']).'">';}
?>
<div id="content"><section class="vf vk">
<h1>Kassenverwaltung · <?= $year ?></h1>
<p>Frei anlegbare Konten für Bargeld, Bank, PayPal und weitere Zahlungswege. Umbuchungen verändern den Gesamtbestand nicht.</p>
<?php if ($flash): ?><p class="alert <?= $flash[0]==='ok'?'':'error' ?>"><?=kh($flash[1])?></p><?php endif; ?>
<nav class="vk-links"><a href="finanzen.php">Mitgliedsbeiträge</a>
<?php if(vk_can($user,'kasse.import')):?><a href="kasse_import.php">CSV-Import</a><?php endif; ?>
<?php if(vk_can($user,'kasse.export')):?><a href="kasse_export.php?format=csv&amp;jahr=<?=$year?>">CSV-Export</a><a href="kasse_export.php?format=pdf&amp;jahr=<?=$year?>">Jahresbericht PDF</a><?php endif; ?>
<?php if(vk_can($user,'kasse.pruefung')):?><a href="kasse_protokoll.php">Prüfprotokoll</a><?php endif; ?>
<?php if((int)$user['rights']>=5||vk_can($user,'beitraege.tarife')):?><a href="kasse_admin.php">Rechte &amp; Beiträge</a><?php endif; ?></nav>
<div class="panel"><form method="get" class="controls"><div class="field"><label for="jahr">Jahr</label><input name="jahr" id="jahr" type="number" min="2000" max="2099" value="<?=$year?>"></div><div class="field"><label for="konto">Konto</label><select name="konto" id="konto"><option value="0">Alle Konten</option><?php foreach($accounts as $a):?><option value="<?=(int)$a['id']?>" <?=$selected===(int)$a['id']?'selected':''?>><?=kh($a['name'])?></option><?php endforeach;?></select></div><button>Auswerten</button></form></div>
<?php $total=0;foreach($balances as $a)$total+=$a['saldo_cent']; ?>
<div class="cards"><div class="card"><small>Bestand am 31.12.<?=$year?></small><strong><?=vf_money($total)?></strong></div><div class="card"><small>Konten (Auswahl)</small><strong><?=count($balances)?></strong></div></div>
<div class="panel"><h2>Kontenübersicht</h2><div class="scroll"><table><thead><tr><th>Konto</th><th>Typ</th><th class="right">Anfang</th><th class="right">Einnahmen</th><th class="right">Ausgaben</th><th class="right">Jahresendsaldo</th></tr></thead><tbody>
<?php foreach($balances as $a):?><tr><td><?=kh($a['name'])?> <?=$a['aktiv']?'':'(inaktiv)'?></td><td><?=kh($a['typ'])?></td><td class="right"><?=vf_money((int)$a['anfangsbestand_cent'])?></td><td class="right"><?=vf_money($a['einnahmen_cent'])?></td><td class="right"><?=vf_money($a['ausgaben_cent'])?></td><td class="right"><?=vf_money($a['saldo_cent'])?></td></tr><?php endforeach;?>
<?php if(!$balances):?><tr><td colspan="6">Noch kein Konto angelegt. Bitte zuerst eines erstellen.</td></tr><?php endif;?></tbody></table></div><p class="tiny muted">Umbuchungen sind in den Salden enthalten, zählen aber nicht als externe Einnahmen/Ausgaben. Der Anfangsbestand gilt ab dem Eröffnungsdatum des Kontos.</p></div>
<?php if(vk_can($user,'kasse.konten')):?>
<div class="panel"><h2>Konto anlegen</h2><form method="post" action="kasse_process.php" class="controls"><?=ktoken()?><input type="hidden" name="action" value="create_account"><div class="field"><label>Name</label><input name="name" maxlength="120" required placeholder="z. B. Vereinskonto"></div><div class="field"><label>Kontotyp (frei)</label><input name="typ" maxlength="40" required placeholder="Bank / Bargeld / PayPal"></div><div class="field"><label>Anfangsbestand €</label><input name="anfang" value="0,00" required></div><div class="field"><label>Stichtag</label><input type="date" name="datum" value="<?=date('Y-m-d')?>" required></div><button>Konto erstellen</button></form></div>
<div class="panel"><h2>Konten bearbeiten</h2><?php foreach($accounts as $a):?><form method="post" action="kasse_process.php" class="controls vk-edit"><?=ktoken()?><input type="hidden" name="action" value="edit_account"><input type="hidden" name="id" value="<?=(int)$a['id']?>"><div class="field"><label>Name</label><input name="name" value="<?=kh($a['name'])?>" maxlength="120" required></div><div class="field"><label>Typ</label><input name="typ" value="<?=kh($a['typ'])?>" maxlength="40" required></div><label class="vk-check"><input type="checkbox" name="aktiv" <?=$a['aktiv']?'checked':''?>> Aktiv</label><button>Speichern</button></form><?php endforeach;?></div>
<?php endif; ?>
<?php if(vk_can($user,'kasse.buchen')):?>
<div class="panel"><h2>Einnahme oder Ausgabe</h2><form method="post" action="kasse_process.php"><?=ktoken()?><input type="hidden" name="action" value="book"><div class="controls"><div class="field"><label>Konto</label><select name="konto" required><?php foreach(vk_accounts() as $a):?><option value="<?=(int)$a['id']?>"><?=kh($a['name'])?></option><?php endforeach;?></select></div><div class="field"><label>Art</label><select name="art"><option value="einnahme">Einnahme</option><option value="ausgabe">Ausgabe</option></select></div><div class="field"><label>Betrag €</label><input name="betrag" placeholder="15,00" required></div><div class="field"><label>Datum</label><input type="date" name="datum" value="<?=date('Y-m-d')?>" required></div></div><div class="controls"><div class="field"><label>Kategorie</label><input name="kategorie" maxlength="100" placeholder="z. B. Vereinsfest"></div><div class="field"><label>Beschreibung</label><input name="beschreibung" maxlength="500" required></div><div class="field"><label>Belegnummer</label><input name="belegnummer" maxlength="100"></div></div><button onclick="return confirm('Buchung verbindlich speichern?')">Buchung speichern</button></form></div>
<div class="panel"><h2>Umbuchung zwischen Konten</h2><form method="post" action="kasse_process.php"><?=ktoken()?><input type="hidden" name="action" value="transfer"><div class="controls"><div class="field"><label>Von Konto</label><select name="von"><?php foreach(vk_accounts() as $a):?><option value="<?=(int)$a['id']?>"><?=kh($a['name'])?></option><?php endforeach;?></select></div><div class="field"><label>Nach Konto</label><select name="nach"><?php foreach(array_reverse(vk_accounts()) as $a):?><option value="<?=(int)$a['id']?>"><?=kh($a['name'])?></option><?php endforeach;?></select></div><div class="field"><label>Betrag €</label><input name="betrag" required placeholder="100,00"></div><div class="field"><label>Datum</label><input name="datum" type="date" value="<?=date('Y-m-d')?>"></div></div><label>Beschreibung</label><input name="beschreibung" placeholder="Kasse auffüllen" maxlength="500" required><p><button onclick="return confirm('Umbuchung auf beiden Konten speichern?')">Umbuchen</button></p></form></div>
<?php endif; ?>
<div class="panel"><h2>Jahresbuchungen (maximal 250)</h2><div class="scroll"><table><thead><tr><th>Datum / Nr.</th><th>Konto</th><th>Art</th><th>Beschreibung</th><th class="right">Betrag</th><th>Beleg / Aktion</th></tr></thead><tbody>
<?php foreach($entries as $b):$void=$b['storniert_am']!==null;?><tr class="<?=$void?'storno':''?>"><td><?=kh($b['datum'])?> <small>#<?=(int)$b['id']?></small></td><td><?=kh($b['kontoname'])?></td><td><?=kh($b['art'])?></td><td><?=kh($b['beschreibung'])?><div class="tiny muted"><?=kh($b['kategorie'])?> <?=kh($b['belegnummer'])?></div><?php if($void):?><div class="tiny">Storniert: <?=kh($b['storno_grund'])?></div><?php endif;?></td><td class="right"><?=str_contains((string)$b['art'],'aus')||$b['art']==='ausgabe'?'-':'+'?><?=vf_money((int)$b['betrag_cent'])?></td><td>
<?php if(vk_can($user,'kasse.export')):?><a href="kasse_export.php?format=receipt&amp;id=<?=(int)$b['id']?>">PDF-Quittung</a> <?php endif; ?>
<?php if(vk_can($user,'kasse.belege')):?><a href="kasse_beleg.php?buchung=<?=(int)$b['id']?>">Anhänge</a><?php endif; ?>
<?php if(!$void&&$b['zahlung_id']===null&&vk_can($user,'kasse.storno')):?><form method="post" action="kasse_process.php" class="inline"><?=ktoken()?><input type="hidden" name="action" value="void"><input type="hidden" name="id" value="<?=(int)$b['id']?>"><input name="grund" placeholder="Stornogrund" minlength="5" maxlength="250" required><button class="danger" onclick="return confirm('Buchung unwiderruflich stornieren?')">Storno</button></form><?php endif; ?>
</td></tr><?php endforeach;?><?php if(!$entries):?><tr><td colspan="6">Noch keine Buchungen im gewählten Zeitraum.</td></tr><?php endif;?></tbody></table></div></div>
</section></div><?php require __DIR__ . '/inc/footer.inc.php'; ?>