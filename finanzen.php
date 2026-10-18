<?php
/**
 * Vereinssystem 2.3.1 – Modul: finanzen.
 * Pfad: finanzen.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
require_once __DIR__.'/inc/connect.inc.php';
require_once __DIR__.'/inc/checkuser.inc.php';
require_once __DIR__.'/inc/finance.php';
if((int)$user['rights']<4){header('Location: finanzkonto.php');exit;}
try{$year=vf_year((string)($_GET['jahr']??date('Y')));}catch(DomainException $e){$year=(int)date('Y');}
$ready=vf_ready();$flash=$_SESSION['vf_flash']??null;unset($_SESSION['vf_flash']);
$_SESSION['vf_nonce']=bin2hex(random_bytes(20));
$search=trim((string)($_GET['q']??''));$search=substr($search,0,100);
$members=[];$stats=['soll'=>0,'ist'=>0,'offen'=>0,'guthaben'=>0];
if($ready){
 $params=[];$sql='SELECT mitglieder_id,mitgliedsnummer,vorname,nachname FROM mitglieder WHERE status=1';
 if($search!==''){$sql.=' AND (vorname LIKE ? OR nachname LIKE ? OR mitgliedsnummer LIKE ?)';$params=array_fill(0,3,'%'.$search.'%');}
 $sql.=' ORDER BY nachname,vorname LIMIT 151';$members=vf_all($sql,$params);
 $truncated=count($members)>150;$members=array_slice($members,0,150);
 foreach($members as &$m){$m['finance']=vf_totals((int)$m['mitglieder_id'],$year);foreach($stats as $key=>&$val)$val+=$m['finance'][$key];unset($val);}unset($m);
}
require __DIR__.'/inc/head.inc.php';
?>
<div id="content"><section class="vf">
<h1>Beitragsverwaltung – <?=vf_escape($year)?></h1>
<p class="muted">Sollstellungen, verbuchte Eingänge, offene Beiträge und unverrechnetes Guthaben.</p>
<?php if($flash):?><p class="alert <?= $flash[0]==='ok'?'':'error' ?>"><?=vf_escape($flash[1])?></p><?php endif; ?>
<?php if(!$ready): ?>
 <p class="alert error">Die drei Finanztabellen sind noch nicht installiert. <?=((int)$user['rights']>=5)?'<a href="upgrade.php">Upgrade auf 2.1.0 ausführen</a>':'Bitte Administrator benachrichtigen.'?></p>
<?php else: ?>
<div class="panel"><form class="controls" method="get">
 <div class="field"><label for="q">Mitglied suchen</label><input id="q" name="q" value="<?=vf_escape($search)?>" placeholder="Name oder Mitgliedsnummer"></div>
 <div class="field"><label for="jahr">Abrechnungsjahr</label><input id="jahr" name="jahr" type="number" min="2000" max="2099" value="<?=$year?>"></div><button>Suchen / filtern</button>
</form></div>
<div class="cards">
 <div class="card"><small>Soll (Auswahl)</small><strong><?=vf_money($stats['soll'])?></strong></div>
 <div class="card"><small>Ist, zugeordnet</small><strong><?=vf_money($stats['ist'])?></strong></div>
 <div class="card due"><small>Offen</small><strong><?=vf_money($stats['offen'])?></strong></div>
 <div class="card credit"><small>Guthaben insgesamt</small><strong><?=vf_money($stats['guthaben'])?></strong></div>
</div>
<p class="tiny muted">Die Summen beziehen sich auf die unten aufgelisteten Mitglieder und das gewählte Jahr; Guthaben umfasst auch andere Jahre. Noch nicht verteiltes Geld zählt nicht zum Ist. Angezeigt werden maximal 150 Treffer.<?=!empty($truncated)?' Bitte Suche eingrenzen.':''?></p>
<?php if((int)$user['rights']>=5): ?>
<div class="panel"><h2>Monatliche Sollstellungen erzeugen</h2>
<p>Übernimmt die <strong>aktuellen</strong> Beitragssätze, die aktiven Abteilungsumlagen und die derzeitigen Mitgliedszugehörigkeiten. Bereits erzeugte Sollstellungen werden nicht verändert. Für zurückliegende Monate können die heutigen Daten von historischen Verhältnissen abweichen.</p>
<form method="post" action="finanzen_process.php" class="controls">
 <input type="hidden" name="vs_csrf" value="<?=vf_escape($_SESSION['vs_csrf'])?>"><input type="hidden" name="nonce" value="<?=vf_escape($_SESSION['vf_nonce'])?>"><input type="hidden" name="action" value="generate"><input type="hidden" name="jahr" value="<?=$year?>">
 <div class="field"><label for="periode">Beitragsmonat</label><input id="periode" type="month" name="periode" value="<?=date('Y-m')?>" required></div><button onclick="return confirm('Sollstellungen für diesen Monat verbindlich erzeugen?')">Sollstellungen erzeugen</button>
</form></div>
<?php endif; ?>
<h2>Mitgliedskonten</h2><div class="scroll"><table><thead><tr><th>Mitglied</th><th class="right">Soll</th><th class="right">Ist</th><th class="right">Offen</th><th class="right">Guthaben</th><th>Aktion</th></tr></thead><tbody>
<?php foreach($members as $m):$a=$m['finance'];?><tr><td><?=vf_escape($m['nachname'].' '.$m['vorname'])?><div class="tiny muted"><?=vf_escape($m['mitgliedsnummer'])?></div></td><td class="right"><?=vf_money($a['soll'])?></td><td class="right"><?=vf_money($a['ist'])?></td><td class="right <?= $a['offen']?'negative':''?>"><?=vf_money($a['offen'])?></td><td class="right <?= $a['guthaben']?'positive':''?>"><?=vf_money($a['guthaben'])?></td><td><a href="finanzkonto.php?mitglied=<?=(int)$m['mitglieder_id']?>&amp;jahr=<?=$year?>">Buchungen öffnen</a></td></tr><?php endforeach; ?>
<?php if(!$members):?><tr><td colspan="6">Keine Mitglieder gefunden.</td></tr><?php endif; ?></tbody></table></div>
<?php endif; ?></section></div>
<?php require __DIR__.'/inc/footer.inc.php'; ?>
