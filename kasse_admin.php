<?php
/**
 * Vereinssystem 2.3.0 – Modul: kasse admin.
 * Pfad: kasse_admin.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/** Administrationsmaske für granulare Kassenrechte und versionierte Beitragssätze. */
require_once __DIR__.'/inc/connect.inc.php';
require_once __DIR__.'/inc/checkuser.inc.php';
require_once __DIR__.'/inc/cash.php';
if ((int)$user['rights'] !== 5 && !vk_can($user,'beitraege.tarife')) {http_response_code(403);exit('Zugriff verweigert.');}
$flash=$_SESSION['vk_flash']??null;unset($_SESSION['vk_flash']);
$rates=vk_ready()?vf_all('SELECT t.*,a.name AS abteilungname FROM vs_tarife t LEFT JOIN abteilungen a ON a.abteilungs_id=t.abteilung_id ORDER BY t.gueltig_ab DESC,t.id DESC LIMIT 150'):[];
$departments=vf_all('SELECT abteilungs_id,name FROM abteilungen WHERE status=1 ORDER BY name');
$members=(int)$user['rights']===5?vf_all('SELECT mitglieder_id,vorname,nachname,rights FROM mitglieder WHERE status=1 ORDER BY nachname,vorname LIMIT 300'):[];
$permissions=(int)$user['rights']===5&&vk_ready()?vf_all('SELECT mitglied_id,recht FROM vs_berechtigungen ORDER BY mitglied_id,recht'):[];
$permissionLookup=[];foreach($permissions as $p)$permissionLookup[(int)$p['mitglied_id']][$p['recht']]=true;
$_SESSION['vk_nonce']=bin2hex(random_bytes(20));
$title='Kassenverwaltung – Einstellungen';require __DIR__.'/inc/head.inc.php';
function ka_h(mixed $v): string {return vf_escape($v);}
?>
<div id="content"><section class="vf vk"><p><a href="kasse.php">← Kassenverwaltung</a></p><h1>Beitragstarife und Rechte</h1>
<?php if($flash):?><p class="alert <?=$flash[0]==='ok'?'':'error'?>"><?=ka_h($flash[1])?></p><?php endif;?>
<?php if(vk_can($user,'beitraege.tarife')):?>
<div class="panel"><h2>Neuen Beitragssatz gültig ab Stichtag anlegen</h2><p>Der zum Monatsersten gültige Satz wird bei der neuen Sollstellung fest gespeichert. Bereits erzeugte Monatsforderungen bleiben unverändert. Beitragssätze sind append-only.</p>
<form class="controls" method="post" action="kasse_process.php"><input type="hidden" name="vs_csrf" value="<?=ka_h($_SESSION['vs_csrf'])?>"><input type="hidden" name="vk_nonce" value="<?=ka_h($_SESSION['vk_nonce'])?>"><input type="hidden" name="action" value="rate"><div class="field"><label>Beitragsart</label><select name="art"><option value="kind">Kinder</option><option value="erwachsen">Erwachsene</option><option value="abteilung">Abteilungsumlage</option></select></div><div class="field"><label>Abteilung (nur bei Umlage)</label><select name="abteilung"><option value="0">Keine</option><?php foreach($departments as $d):?><option value="<?=(int)$d['abteilungs_id']?>"><?=ka_h($d['name'])?></option><?php endforeach;?></select></div><div class="field"><label>Ab</label><input type="date" name="gueltig_ab" value="<?=date('Y-m-01')?>" required></div><div class="field"><label>Betrag €</label><input name="betrag" required placeholder="15,00"></div><button>Tarif festlegen</button></form>
<h3>Tarifhistorie</h3><div class="scroll"><table><thead><tr><th>Ab</th><th>Beitragsart</th><th>Abteilung</th><th>Betrag</th></tr></thead><tbody><?php foreach($rates as $r):?><tr><td><?=ka_h($r['gueltig_ab'])?></td><td><?=ka_h($r['art'])?></td><td><?=ka_h($r['abteilungname']??'')?></td><td><?=vf_money((int)$r['betrag_cent'])?></td></tr><?php endforeach;?></tbody></table></div></div>
<?php endif;?>
<?php if((int)$user['rights']===5):?>
<div class="panel"><h2>Kassenberechtigungen zuweisen</h2><p>Nur Administratoren vergeben Zusatzrechte. Level 5 besitzt alle Rechte automatisch. Das bisherige Rechtesystem der Altmodule bleibt unabhängig bestehen.</p>
<form class="controls" method="post" action="kasse_process.php"><input type="hidden" name="vs_csrf" value="<?=ka_h($_SESSION['vs_csrf'])?>"><input type="hidden" name="vk_nonce" value="<?=ka_h($_SESSION['vk_nonce'])?>"><input type="hidden" name="action" value="permission"><div class="field"><label>Mitglied</label><select name="mitglied"><?php foreach($members as $m):?><option value="<?=(int)$m['mitglieder_id']?>"><?=ka_h($m['nachname'].' '.$m['vorname'].' (Level '.$m['rights'].')')?></option><?php endforeach;?></select></div><div class="field"><label>Zusatzrecht</label><select name="recht"><?php foreach(VK_RECHTE as $code=>$description):?><option value="<?=ka_h($code)?>"><?=ka_h($description)?></option><?php endforeach;?></select></div><button name="gewähren" value="1">Erteilen</button><button class="minor" name="entziehen" value="1">Entziehen</button></form>
<h3>Vergebene Berechtigungen</h3><div class="scroll"><table><thead><tr><th>Mitglied-ID</th><th>Berechtigung</th></tr></thead><tbody><?php foreach($permissions as $p):?><tr><td>#<?=(int)$p['mitglied_id']?></td><td><?=ka_h(VK_RECHTE[$p['recht']]??$p['recht'])?></td></tr><?php endforeach;?></tbody></table></div></div>
<?php endif;?></section></div><?php require __DIR__.'/inc/footer.inc.php'; ?>