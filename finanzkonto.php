<?php
/**
 * Vereinssystem 2.3.1 – Modul: finanzkonto.
 * Pfad: finanzkonto.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
require_once __DIR__.'/inc/connect.inc.php';
require_once __DIR__.'/inc/checkuser.inc.php';
require_once __DIR__.'/inc/finance.php';
require_once __DIR__.'/inc/cash.php';
$isStaff=(int)$user['rights']>=4;
$memberId=$isStaff?max(1,(int)($_GET['mitglied']??$user['mitglieder_id'])):(int)$user['mitglieder_id'];
try{$year=vf_year((string)($_GET['jahr']??date('Y')));}catch(DomainException $e){$year=(int)date('Y');}
try{$member=vf_member($memberId);$ready=vf_ready();}catch(Throwable $e){http_response_code(404);exit('Mitglied nicht gefunden.');}
$charges=$ready?vf_charges($memberId,$year):[];
$payments=$ready?vf_payments($memberId,$year):[];
$allocations=$ready?vf_allocations_for_year($memberId,$year):[];
$totals=$ready?vf_totals($memberId,$year):['soll'=>0,'ist'=>0,'offen'=>0,'guthaben'=>0];
$flash=$_SESSION['vf_flash']??null;unset($_SESSION['vf_flash']);
$_SESSION['vf_nonce']=bin2hex(random_bytes(20));
$openCharges=array_values(array_filter($charges,fn($r)=>$r['storniert_am']===null&&(int)$r['betrag_cent']>(int)$r['ist_cent']));
require __DIR__.'/inc/head.inc.php';
?>
<div id="content"><section class="vf">
<p><a href="<?= $isStaff?'finanzen.php?jahr='.$year:'member_show.php?id='.$memberId ?>">← Zurück</a></p>
<h1>Beitragskonto: <?=vf_escape($member['vorname'].' '.$member['nachname'])?></h1>
<p class="muted">Beitragsjahr <?=$year?> · <?= $isStaff?'Verwaltung und Buchung':'Persönliche Finanzübersicht (nur Lesen)' ?></p>
<?php if($flash):?><p class="alert <?=$flash[0]==='ok'?'':'error'?>"><?=vf_escape($flash[1])?></p><?php endif; ?>
<?php if(!$ready):?><p class="alert error">Die Finanzdatenbank ist noch nicht eingerichtet. Bitte Administrator kontaktieren.</p>
<?php else: ?>
<form method="get" class="controls panel"><input type="hidden" name="mitglied" value="<?=$memberId?>"><div class="field"><label for="jahr">Beitragsjahr</label><input type="number" id="jahr" name="jahr" min="2000" max="2099" value="<?=$year?>"></div><button>Jahr anzeigen</button></form>
<div class="cards"><div class="card"><small>Beitragssoll <?=$year?></small><strong><?=vf_money($totals['soll'])?></strong></div><div class="card"><small>Verrechnete Zahlungen</small><strong><?=vf_money($totals['ist'])?></strong></div><div class="card due"><small>Noch offen <?=$year?></small><strong><?=vf_money($totals['offen'])?></strong></div><div class="card credit"><small>Nicht zugeordnetes Guthaben</small><strong><?=vf_money($totals['guthaben'])?></strong></div></div>
<?php if($isStaff): ?>
<div class="panel"><h2>Zahlungseingang buchen</h2>
<p>Eine Zahlung wird erst der gewählten offenen Forderung und anschließend weiteren offenen Beiträgen dieses Mitglieds (älteste zuerst) zugeordnet. Nicht zugeordnete Beträge bleiben Guthaben.</p>
<form method="post" action="finanzen_process.php">
<input type="hidden" name="vs_csrf" value="<?=vf_escape($_SESSION['vs_csrf'])?>"><input type="hidden" name="nonce" value="<?=vf_escape($_SESSION['vf_nonce'])?>"><input type="hidden" name="action" value="payment"><input type="hidden" name="mitglied" value="<?=$memberId?>"><input type="hidden" name="jahr" value="<?=$year?>">
<?php if (vk_ready()): ?>
<div class="controls"><div class="field"><label for="konto">Eingang auf Konto</label><select name="konto" id="konto" required><option value="">Konto wählen</option><?php foreach(vk_accounts() as $k): ?><option value="<?=(int)$k['id']?>"><?=vf_escape($k['name'])?></option><?php endforeach; ?></select></div></div>
<?php endif; ?>
<div class="controls"><div class="field"><label for="betrag">Betrag (€)</label><input id="betrag" name="betrag" inputmode="decimal" placeholder="15,00" required maxlength="12"></div>
<div class="field"><label for="datum">Zahlung erhalten am</label><input id="datum" type="date" name="datum" value="<?=date('Y-m-d')?>" required></div>
<div class="field"><label for="weg">Zahlungsweg</label><select id="weg" name="weg"><option>Überweisung</option><option>Bar</option><option>Lastschrift</option><option>PayPal</option><option>Sonstiges</option></select></div></div>
<div class="controls"><div class="field"><label for="forderung">Zuerst verrechnen mit</label><select id="forderung" name="forderung"><option value="0">Älteste offene Beiträge zuerst</option><?php foreach($openCharges as $c): ?><option value="<?=(int)$c['id']?>"><?=vf_escape($c['periode'].' – '.$c['beschreibung'].' (offen '.vf_money((int)$c['betrag_cent']-(int)$c['ist_cent']).')')?></option><?php endforeach; ?></select></div>
<div class="field"><label for="zahler">Gezahlt von (optional)</label><input id="zahler" name="zahler" maxlength="190" placeholder="z. B. Elternteil / Kontoinhaber"></div><div class="field"><label for="referenz">Verwendungszweck / Beleg-Nr.</label><input id="referenz" name="referenz" maxlength="190" placeholder="z. B. Kontoauszug Nr. 04"></div></div>
<label for="bemerkung">Bemerkung (optional)</label><textarea id="bemerkung" name="bemerkung" maxlength="500"></textarea>
<p><button type="submit" onclick="return confirm('Zahlung endgültig als Eingang erfassen?')">Zahlung erfassen</button></p>
</form></div>
<?php if($totals['guthaben']>0):?><div class="panel"><h2>Guthaben verrechnen</h2><p>Bereits gebuchtes, unverrechnetes Guthaben vorhandenen offenen Forderungen zuordnen.</p>
<form method="post" action="finanzen_process.php" class="inline"><input type="hidden" name="vs_csrf" value="<?=vf_escape($_SESSION['vs_csrf'])?>"><input type="hidden" name="nonce" value="<?=vf_escape($_SESSION['vf_nonce'])?>"><input type="hidden" name="action" value="reconcile"><input type="hidden" name="mitglied" value="<?=$memberId?>"><input type="hidden" name="jahr" value="<?=$year?>"><button class="minor">Guthaben automatisch verrechnen</button></form></div><?php endif; ?>
<?php endif; ?>
<h2>Beitragsforderungen <?=$year?></h2>
<div class="scroll"><table><thead><tr><th>Monat / Position</th><th>Fällig</th><th class="right">Soll</th><th class="right">Ist</th><th class="right">Offen</th><?php if($isStaff):?><th>Korrektur</th><?php endif; ?></tr></thead><tbody>
<?php foreach($charges as $c):$void=$c['storniert_am']!==null;$open=$void?0:max(0,(int)$c['betrag_cent']-(int)$c['ist_cent']);?><tr class="<?=$void?'storno':''?>"><td><?=vf_escape($c['periode'])?> <span class="tiny muted"><?=vf_escape($c['beschreibung'])?></span><?php if($void):?><div class="tiny">Storniert am <?=vf_escape($c['storniert_am'])?> durch #<?=(int)$c['storniert_von']?>: <?=vf_escape($c['storno_grund'])?></div><?php endif; ?></td><td><?=vf_escape($c['faellig_am'])?></td><td class="right"><?=vf_money((int)$c['betrag_cent'])?></td><td class="right"><?=$void?'–':vf_money((int)$c['ist_cent'])?></td><td class="right <?=$open?'negative':''?>"><?=vf_money($open)?></td>
<?php if($isStaff):?><td><?php if(!$void && (int)$c['ist_cent']===0 && (int)$user['rights']>=5):?><form class="inline" method="post" action="finanzen_process.php"><input type="hidden" name="vs_csrf" value="<?=vf_escape($_SESSION['vs_csrf'])?>"><input type="hidden" name="nonce" value="<?=vf_escape($_SESSION['vf_nonce'])?>"><input type="hidden" name="action" value="void_charge"><input type="hidden" name="mitglied" value="<?=$memberId?>"><input type="hidden" name="jahr" value="<?=$year?>"><input type="hidden" name="id" value="<?=(int)$c['id']?>"><input aria-label="Stornogrund" name="grund" placeholder="Grund (min. 5 Zeichen)" minlength="5" maxlength="250" required><button class="danger" onclick="return confirm('Forderung stornieren?')">Storno</button></form><?php endif; ?></td><?php endif; ?></tr><?php endforeach; ?>
<?php if(!$charges):?><tr><td colspan="6">Für <?=$year?> liegen noch keine Sollstellungen vor.</td></tr><?php endif; ?></tbody></table></div>
<h2>Erfasste Zahlungen <?=$year?></h2>
<p class="tiny muted">Angezeigt werden Zahlungseingänge dieses Kalenderjahres. Die Zuordnung kann auch zu Forderungen anderer Jahre erfolgt sein.</p>
<div class="scroll"><table><thead><tr><th>Beleg / Datum</th><th>Zahlungsweg</th><th class="right">Eingang</th><th class="right">Verrechnet</th><th>Bemerkung</th><?php if($isStaff):?><th>Korrektur</th><?php endif; ?></tr></thead><tbody>
<?php foreach($payments as $p):$void=$p['storniert_am']!==null;?><tr class="<?=$void?'storno':''?>"><td>#<?=(int)$p['id']?> · <?=vf_escape($p['gezahlt_am'])?><div class="tiny muted"><?=vf_escape($p['referenz'])?></div></td><td><?=vf_escape($p['zahlungsweg'])?><?php if($p['zahler_name']!==''):?><div class="tiny muted">Von: <?=vf_escape($p['zahler_name'])?></div><?php endif; ?></td><td class="right"><?=vf_money((int)$p['betrag_cent'])?></td><td class="right"><?=$void?'–':vf_money((int)$p['verwendet_cent'])?></td><td><?=vf_escape($p['bemerkung'])?>
<?php if(!$void && !empty($allocations[(int)$p['id']])):?>
<div class="tiny muted">Zuordnung: <?php foreach($allocations[(int)$p['id']] as $a):?><?=vf_escape($a['periode'].' '.vf_money((int)$a['betrag_cent']))?> · <?php endforeach; ?></div>
<?php endif; ?>
<?php if($void):?><div class="tiny">Storno am <?=vf_escape($p['storniert_am'])?> durch #<?=(int)$p['storniert_von']?>: <?=vf_escape($p['storno_grund'])?></div><?php endif; ?></td>
<?php if($isStaff):?><td><?php if(!$void):?><form class="inline" method="post" action="finanzen_process.php"><input type="hidden" name="vs_csrf" value="<?=vf_escape($_SESSION['vs_csrf'])?>"><input type="hidden" name="nonce" value="<?=vf_escape($_SESSION['vf_nonce'])?>"><input type="hidden" name="action" value="void_payment"><input type="hidden" name="mitglied" value="<?=$memberId?>"><input type="hidden" name="jahr" value="<?=$year?>"><input type="hidden" name="id" value="<?=(int)$p['id']?>"><input aria-label="Stornogrund" name="grund" placeholder="Grund (min. 5 Zeichen)" minlength="5" maxlength="250" required><button class="danger" onclick="return confirm('Zahlung stornieren? Bereits zugeordnete Beiträge werden wieder offen.')">Storno</button></form><?php endif; ?></td><?php endif; ?></tr><?php endforeach; ?>
<?php if(!$payments):?><tr><td colspan="6">Keine Zahlungen in diesem Jahr gebucht.</td></tr><?php endif; ?></tbody></table></div>
<p class="muted tiny">Stornierte Einträge bleiben mit Zeitstempel, Bearbeiter-ID und Grund in der Datenbank erhalten. Die Jahres-Sollwerte werden bei späteren Änderungen der Beitragssätze nicht rückwirkend neu berechnet.</p>
<?php endif; ?></section></div>
<?php require __DIR__.'/inc/footer.inc.php'; ?>
