<?php
/**
 * Vereinssystem 2.3.1 – Modul: kasse import.
 * Pfad: kasse_import.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/** Zweistufiger CSV-Import: Vorschau in Session, explizite Bestätigung, Duplikatsperre. */
require_once __DIR__.'/inc/connect.inc.php';
require_once __DIR__.'/inc/checkuser.inc.php';
require_once __DIR__.'/inc/cash.php';
vk_require($user,'kasse.import');
$notice='';$error='';$preview=$_SESSION['vk_import_preview']??null;
if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
    try {
        if(!vk_ready())throw new DomainException('Bitte zuerst upgrade.php ausführen.');
        $mode=(string)($_POST['mode']??'');
        if($mode==='preview') {
            if(!isset($_FILES['csv'])||$_FILES['csv']['error']!==UPLOAD_ERR_OK)throw new DomainException('Keine gültige CSV-Datei hochgeladen.');
            if($_FILES['csv']['size']>1048576)throw new DomainException('CSV maximal 1 MB.');
            $account=(int)($_POST['konto']??0);vk_account($account,false,true);
            $fp=fopen($_FILES['csv']['tmp_name'],'rb');if(!$fp)throw new DomainException('CSV konnte nicht geöffnet werden.');
            $hash=hash_file('sha256',$_FILES['csv']['tmp_name']);
            $head=fgetcsv($fp,0,';');
            if(isset($head[0]))$head[0]=preg_replace('/^\xEF\xBB\xBF/','',$head[0]);
            if($head!==['datum','art','betrag','kategorie','beschreibung','belegnummer'])throw new DomainException('Spalten erforderlich: datum;art;betrag;kategorie;beschreibung;belegnummer');
            $rows=[];$n=1;
            while(($row=fgetcsv($fp,0,';'))!==false){$n++;
                if(count($row)===1&&trim((string)$row[0])==='')continue;
                if(count($row)!==6)throw new DomainException('Zeile '.$n.': sechs Spalten erforderlich.');
                [$date,$kind,$amount,$category,$description,$receipt]=$row;
                vf_date($date);if(!in_array($kind,['einnahme','ausgabe'],true))throw new DomainException('Zeile '.$n.': Art muss einnahme oder ausgabe sein.');
                $cent=vk_amount($amount);vk_text($category,100);vk_text($description,500,true);vk_text($receipt,100);
                $rows[]=['date'=>$date,'kind'=>$kind,'cent'=>$cent,'amount'=>$amount,'category'=>$category,'description'=>$description,'receipt'=>$receipt,'index'=>$n];
                if(count($rows)>100)throw new DomainException('Maximal 100 CSV-Zeilen pro Import.');
            }
            fclose($fp);if(!$rows)throw new DomainException('CSV enthält keine Buchungszeilen.');
            $preview=['user'=>(int)$user['mitglieder_id'],'account'=>$account,'hash'=>$hash,'rows'=>$rows,'token'=>bin2hex(random_bytes(20))];
            $_SESSION['vk_import_preview']=$preview;
            $notice='Vorschau bereit. Noch nichts gebucht.';
        } elseif ($mode==='commit') {
            if(!$preview || $preview['user']!==(int)$user['mitglieder_id'] || !hash_equals((string)$preview['token'],(string)($_POST['token']??'')))throw new DomainException('Importvorschau abgelaufen, bitte erneut hochladen.');
            // Einmalig: gegen doppelte Übermittlung durch Refresh.
            unset($_SESSION['vk_import_preview']);
            $pdo=vf_db();$pdo->beginTransaction();$inserted=0;$skipped=0;
            try {
                vk_account($preview['account'],true,true);
                foreach($preview['rows'] as $row){
                    $importKey=hash('sha256',$preview['hash'].'|'.$preview['account'].'|'.$row['index']);
                    if(vf_one('SELECT id FROM vs_kassenbuch WHERE import_key=?',[$importKey])){$skipped++;continue;}
                    $id=vk_insert_locked($preview['account'],$row['kind'],$row['date'],$row['cent'],
                        $row['category'],$row['description'],$row['receipt'],(int)$user['mitglieder_id'],null,null,$importKey);
                    vk_audit((int)$user['mitglieder_id'],'kasse.csv.import','buchung',$id,['import_hash'=>$preview['hash'],'zeile'=>$row['index']]);
                    $inserted++;
                }
                $pdo->commit();
            }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
            $preview=null;$notice=$inserted.' Buchungen importiert, '.$skipped.' Duplikate übersprungen.';
        } else throw new DomainException('Ungültige Aktion.');
    } catch(Throwable $e){error_log('[Kassenimport] '.$e->getMessage());$error=$e instanceof DomainException?$e->getMessage():'Import fehlgeschlagen; es wurde kein Teilimport gespeichert.';}
}
$title='Kassen-CSV-Import';require __DIR__.'/inc/head.inc.php';
?>
<div id="content"><section class="vf vk"><p><a href="kasse.php">← Kasse</a></p><h1>CSV-Kassenimport</h1>
<p>Importiert normale Einnahmen und Ausgaben. Bereits über das Beitragskonto gebuchte Zahlungen <strong>nicht erneut importieren</strong>! Dies ist kein automatischer Bankabgleich.</p>
<?php if($error):?><p class="alert error"><?=vf_escape($error)?></p><?php endif;?><?php if($notice):?><p class="alert"><?=vf_escape($notice)?></p><?php endif;?>
<div class="panel"><h2>Datei prüfen</h2><p>UTF-8-CSV mit Semikolontrennung und Überschriften: <code>datum;art;betrag;kategorie;beschreibung;belegnummer</code>. Datum ISO <code>2026-10-18</code>; Betrag z. B. <code>12,50</code>. Maximal 100 Buchungen und 1 MB.</p>
<form action="kasse_import.php" method="post" enctype="multipart/form-data"><input type="hidden" name="vs_csrf" value="<?=vf_escape($_SESSION['vs_csrf'])?>"><input type="hidden" name="mode" value="preview"><div class="controls"><div class="field"><label>Zielkonto</label><select name="konto"><?php foreach(vk_accounts() as $a):?><option value="<?=(int)$a['id']?>"><?=vf_escape($a['name'])?></option><?php endforeach;?></select></div><div class="field"><label>CSV-Datei</label><input name="csv" type="file" accept=".csv,text/csv" required></div><button>Vorschau anzeigen</button></div></form></div>
<?php if($preview && $preview['user']===(int)$user['mitglieder_id']):?>
<div class="panel"><h2>Prüfen und bestätigen: <?=count($preview['rows'])?> Positionen</h2><div class="scroll"><table><thead><tr><th>Datum</th><th>Art</th><th>Betrag</th><th>Beschreibung</th></tr></thead><tbody><?php foreach($preview['rows'] as $r):?><tr><td><?=vf_escape($r['date'])?></td><td><?=vf_escape($r['kind'])?></td><td><?=vf_money($r['cent'])?></td><td><?=vf_escape($r['description'])?></td></tr><?php endforeach;?></tbody></table></div><form method="post"><input type="hidden" name="vs_csrf" value="<?=vf_escape($_SESSION['vs_csrf'])?>"><input type="hidden" name="mode" value="commit"><input type="hidden" name="token" value="<?=vf_escape($preview['token'])?>"><button onclick="return confirm('Alle gezeigten Positionen verbindlich buchen?')">Import verbindlich ausführen</button></form></div>
<?php endif;?></section></div><?php require __DIR__.'/inc/footer.inc.php'; ?>
