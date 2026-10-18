<?php
/**
 * Vereinssystem 2.3.0 – Modul: finance.
 * Pfad: inc/finance.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/** Vereinssystem 2.1.0 – Finanzkern. Nie Geldbeträge als Fließkomma verarbeiten. */
function vf_db(): PDO { return db_pdo(); }
function vf_one(string $sql,array $params=[]): ?array {
    $s=vf_db()->prepare($sql); $s->execute($params); return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}
function vf_all(string $sql,array $params=[]): array {
    $s=vf_db()->prepare($sql); $s->execute($params); return $s->fetchAll(PDO::FETCH_ASSOC);
}
function vf_exec(string $sql,array $params=[]): int {
    $s=vf_db()->prepare($sql);$s->execute($params);return $s->rowCount();
}
function vf_escape(mixed $x): string { return htmlspecialchars((string)$x, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function vf_money(int $c): string {return number_format($c/100,2,',','.').' €';}
function vf_parse_amount(string $s): int {
    $s=trim($s);
    if (!preg_match('/^(?:0|[1-9]\d{0,6})(?:[,\.]\d{1,2})?$/D',$s)) throw new DomainException('Betrag bitte ohne Tausenderpunkte, z. B. 15,00, eingeben.');
    $parts=preg_split('/[,\.]/',$s);
    $cent=(int)$parts[0]*100+(isset($parts[1])?(int)str_pad($parts[1],2,'0'):0);
    if($cent<=0 || $cent>100000000)throw new DomainException('Der Betrag muss positiv und höchstens 1.000.000,00 € sein.');
    return $cent;
}
function vf_date(string $raw): string {
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$raw))throw new DomainException('Ungültiges Datum.');
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$raw);
    if(!$date || $date->format('Y-m-d')!==$raw)throw new DomainException('Ungültiges Datum.');
    return $raw;
}
function vf_period(string $raw): string {
    if(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/D',$raw))throw new DomainException('Ungültiger Beitragsmonat (2000–2099).');
    return $raw;
}
function vf_year(string $raw): int {
    if (!preg_match('/^20\d{2}$/D',$raw)) throw new DomainException('Ungültiges Beitragsjahr.');
    return (int)$raw;
}
function vf_ready(): bool {
    try {
        return (int)(vf_one('SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN (\'vs_forderungen\',\'vs_zahlungen\',\'vs_zuordnungen\')')['n']??0)===3;
    } catch(Throwable $e){error_log('[Finance] '. $e->getMessage()); return false;}
}
function vf_require_ready(): void {if(!vf_ready()) throw new DomainException('Die Finanzdatenbank ist noch nicht installiert. Ein Administrator muss upgrade.php ausführen.');}
function vf_member(int $id,bool $lock=false): array {
    $r=vf_one('SELECT mitglieder_id,vorname,nachname,beitritt,geburtstag,mitgliedschaft,status FROM mitglieder WHERE mitglieder_id=?'.($lock?' FOR UPDATE':''),[$id]);
    if(!$r)throw new DomainException('Mitglied nicht gefunden.'); return $r;
}
/** Monatliche Sollstellung: historisch unveränderlicher Betrag, Stichtag Monatserster. */
function vf_generate_month(string $period,int $actor): array {
    vf_require_ready(); vf_period($period);
    $begin=$period.'-01';$end=(new DateTimeImmutable($begin))->modify('last day of this month')->format('Y-m-d');
    $pdo=vf_db();$inserted=0;$skipped=0;$total=0;
    $pdo->beginTransaction();
    try {
        $settingsRows=vf_all('SELECT name,value FROM einstellungen WHERE name IN (\'beitrag_kinder\',\'beitrag_erwachsener\')');
        $settings=[];foreach($settingsRows as $r)$settings[$r['name']]=$r['value'];
        $members=vf_all('SELECT mitglieder_id,geburtstag,beitritt,mitgliedschaft FROM mitglieder WHERE status=1 AND beitritt<=? ORDER BY mitglieder_id',[$end]);
        $stmt=$pdo->prepare('INSERT INTO vs_forderungen (mitglied_id,periode,art,abteilung_id,beschreibung,betrag_cent,faellig_am,erstellt_von) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=id');
        $charges=[];
        foreach($members as $m){
            // Ruhende Mitgliedschaften werden nicht automatisch abgerechnet.
            if((int)$m['mitgliedschaft']===2)continue;
            $birthday=new DateTimeImmutable($m['geburtstag']);
            $age=$birthday->diff(new DateTimeImmutable($begin))->y;
            $key=($age>=18)?'beitrag_erwachsener':'beitrag_kinder';
            $base=(int)($settings[$key]??0);
            // Neue Stichtagstarife überschreiben die historischen 2013-Grundeinstellungen,
            // jedoch nur für noch nicht erzeugte Sollstellungen.
            if (function_exists('vk_rate')) {
                $base=vk_rate($age>=18?'erwachsen':'kind',0,$begin,$base);
            }
            if($base>0)$charges[]=[(int)$m['mitglieder_id'],$period,'grundbeitrag',0,$age>=18?'Vereinsbeitrag Erwachsene':'Vereinsbeitrag Kind',$base,$end,$actor];
        }
        $deps=vf_all('SELECT z.mitglied,z.abteilung,a.name,a.aktivenumlage FROM abteilungszugehoerigkeit z JOIN abteilungen a ON a.abteilungs_id=z.abteilung JOIN mitglieder m ON m.mitglieder_id=z.mitglied WHERE z.aktiv=1 AND a.status=1 AND m.status=1 AND m.mitgliedschaft<>2 AND m.beitritt<=? ORDER BY z.mitglied,z.abteilung',[$end]);
        foreach($deps as $d) {
            $sum=(int)$d['aktivenumlage'];
            if (function_exists('vk_rate')) {
                $sum=vk_rate('abteilung',(int)$d['abteilung'],$begin,$sum);
            }
            if($sum>0)$charges[]=[(int)$d['mitglied'],$period,'abteilung',(int)$d['abteilung'],'Aktivenumlage: '.substr((string)$d['name'],0,165),$sum,$end,$actor];
        }
        foreach($charges as $charge){
            if($charge[5]>100000000)throw new DomainException('Ungültiger Beitragssatz in den Einstellungen.');
            $stmt->execute($charge);
            if($stmt->rowCount()===1){$inserted++;$total+=$charge[5];}else{$skipped++;}
        }
        if (function_exists('vk_ready') && vk_ready()) {
            vk_audit($actor,'beitrag.sollstellung','periode',0,['monat'=>$period,'neu'=>$inserted,'cent'=>$total]);
        }
        $pdo->commit();return ['neu'=>$inserted,'vorhanden'=>$skipped,'cent'=>$total];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
/** Kontosperre verhindert konkurrierende Überbuchungen durch zwei Sitzungen. */
function vf_allocate_locked(int $member,int $paymentId,?int $preferred=null): int {
    $payment=vf_one('SELECT id,betrag_cent FROM vs_zahlungen WHERE id=? AND mitglied_id=? AND storniert_am IS NULL FOR UPDATE',[$paymentId,$member]);
    if(!$payment)throw new DomainException('Zahlung nicht gefunden.');
    $used=(int)(vf_one('SELECT COALESCE(SUM(betrag_cent),0) AS s FROM vs_zuordnungen WHERE zahlung_id=?',[$paymentId])['s']??0);
    $remaining=(int)$payment['betrag_cent']-$used;
    if($remaining<=0)return 0;
    $rows=vf_all('SELECT f.id,f.betrag_cent,COALESCE(SUM(IF(z.storniert_am IS NULL,a.betrag_cent,0)),0) AS bezahlt FROM vs_forderungen f LEFT JOIN vs_zuordnungen a ON a.forderung_id=f.id LEFT JOIN vs_zahlungen z ON z.id=a.zahlung_id WHERE f.mitglied_id=? AND f.storniert_am IS NULL GROUP BY f.id ORDER BY f.faellig_am,f.id',[$member]);
    // MySQL kann FOR UPDATE + GROUP BY ablehnen; explizit Sperren durch die Mitgliedszeile.
    if($preferred!==null){$found=false;foreach($rows as $r)if((int)$r['id']===$preferred)$found=true;if(!$found)throw new DomainException('Ausgewählte Forderung gehört nicht zum Mitglied.');
        usort($rows,static fn($a,$b)=>(($a['id']==$preferred)?-1:(($b['id']==$preferred)?1:0)));
    }
    $insert=vf_db()->prepare('INSERT INTO vs_zuordnungen (zahlung_id,forderung_id,betrag_cent) VALUES (?,?,?) ON DUPLICATE KEY UPDATE betrag_cent=betrag_cent+VALUES(betrag_cent)');
    $allocated=0;
    foreach($rows as $r){$open=(int)$r['betrag_cent']-(int)$r['bezahlt'];$part=min($remaining,max(0,$open));if($part<=0)continue;
        $insert->execute([$paymentId,(int)$r['id'],$part]);$remaining-=$part;$allocated+=$part;if($remaining===0)break;
    }
    return $allocated;
}
function vf_add_payment(int $member,string $amount,string $paidAt,string $channel,string $payer,string $reference,string $note,int $actor,?int $preferred=null,?int $cashAccount=null): array {
    vf_require_ready();$c=vf_parse_amount($amount);vf_date($paidAt);
    if(!in_array($channel,['Überweisung','Bar','Lastschrift','PayPal','Sonstiges'],true))throw new DomainException('Ungültiger Zahlungsweg.');
    if(strlen($payer)>190||strlen($reference)>190||strlen($note)>500)throw new DomainException('Referenz oder Bemerkung zu lang.');
    $pdo=vf_db();$pdo->beginTransaction();
    try {
        vf_member($member,true);
        vf_exec('INSERT INTO vs_zahlungen (mitglied_id,betrag_cent,gezahlt_am,zahlungsweg,zahler_name,referenz,bemerkung,erstellt_von) VALUES (?,?,?,?,?,?,?,?)',[$member,$c,$paidAt,$channel,$payer,$reference,$note,$actor]);
        $id=(int)$pdo->lastInsertId();$allocated=vf_allocate_locked($member,$id,$preferred);
        // Eine Beitragszahlung und ihr Kassenbucheintrag bilden EINE Transaktion.
        if ($cashAccount !== null && function_exists('vk_link_payment_locked')) {
            vk_link_payment_locked($cashAccount,$id,$member,$c,$paidAt,$actor);
        }
        if (function_exists('vk_ready') && vk_ready()) {
            vk_audit($actor,'beitrag.zahlung','zahlung',$id,['mitglied'=>$member,'cent'=>$c,'konto'=>$cashAccount]);
        }
        $pdo->commit();return ['id'=>$id,'zugeordnet'=>$allocated,'guthaben'=>$c-$allocated];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
/** Verrechnet bereits bestehendes Guthaben nach Datum auf offene Forderungen. */
function vf_reconcile(int $member): int {
    vf_require_ready();$pdo=vf_db();$pdo->beginTransaction();$assigned=0;
    try{
        vf_member($member,true);
        $payments=vf_all('SELECT z.id FROM vs_zahlungen z LEFT JOIN vs_zuordnungen a ON a.zahlung_id=z.id WHERE z.mitglied_id=? AND z.storniert_am IS NULL GROUP BY z.id,z.betrag_cent,z.gezahlt_am HAVING z.betrag_cent>COALESCE(SUM(a.betrag_cent),0) ORDER BY z.gezahlt_am,z.id',[$member]);
        foreach($payments as $p)$assigned+=vf_allocate_locked($member,(int)$p['id']);
        $pdo->commit();return $assigned;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function vf_void_payment(int $id,string $reason,int $actor): void {
    if(strlen($reason)<5||strlen($reason)>250)throw new DomainException('Stornogrund: 5 bis 250 Zeichen.');
    $pdo=vf_db();$pdo->beginTransaction();
    try {
        $r=vf_one('SELECT mitglied_id FROM vs_zahlungen WHERE id=?',[$id]);if(!$r)throw new DomainException('Zahlung nicht gefunden.');
        vf_member((int)$r['mitglied_id'],true);
        if(vf_exec('UPDATE vs_zahlungen SET storniert_am=NOW(),storniert_von=?,storno_grund=? WHERE id=? AND storniert_am IS NULL',[$actor,$reason,$id])!==1)throw new DomainException('Zahlung bereits storniert.');
        // Storno einer Beitragszahlung storniert verknüpften Kasseneingang mit.
        if (function_exists('vk_void_linked_payment_locked')) {
            vk_void_linked_payment_locked($id,$actor,$reason);
        }
        if (function_exists('vk_ready') && vk_ready()) vk_audit($actor,'beitrag.storno','zahlung',$id,['grund'=>$reason]);
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function vf_void_charge(int $id,string $reason,int $actor): void {
    if(strlen($reason)<5||strlen($reason)>250)throw new DomainException('Stornogrund: 5 bis 250 Zeichen.');
    $pdo=vf_db();$pdo->beginTransaction();
    try{
        $r=vf_one('SELECT mitglied_id FROM vs_forderungen WHERE id=?',[$id]);if(!$r)throw new DomainException('Forderung nicht gefunden.');
        vf_member((int)$r['mitglied_id'],true);
        $paid=(int)(vf_one('SELECT COALESCE(SUM(a.betrag_cent),0) AS s FROM vs_zuordnungen a JOIN vs_zahlungen z ON z.id=a.zahlung_id WHERE a.forderung_id=? AND z.storniert_am IS NULL',[$id])['s']??0);
        if($paid>0)throw new DomainException('Bezahlte Forderung nicht stornierbar. Zuerst die Zahlung stornieren.');
        if(vf_exec('UPDATE vs_forderungen SET storniert_am=NOW(),storniert_von=?,storno_grund=? WHERE id=? AND storniert_am IS NULL',[$actor,$reason,$id])!==1)throw new DomainException('Forderung bereits storniert.');
        if (function_exists('vk_ready') && vk_ready()) vk_audit($actor,'beitrag.forderung.storno','forderung',$id,['grund'=>$reason]);
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function vf_charges(int $member,int $year): array {
    return vf_all('SELECT f.*,COALESCE(SUM(IF(z.storniert_am IS NULL,a.betrag_cent,0)),0) AS ist_cent FROM vs_forderungen f LEFT JOIN vs_zuordnungen a ON a.forderung_id=f.id LEFT JOIN vs_zahlungen z ON z.id=a.zahlung_id WHERE f.mitglied_id=? AND f.periode BETWEEN ? AND ? GROUP BY f.id ORDER BY f.periode,f.id',[$member,$year.'-01',$year.'-12']);
}
function vf_payments(int $member,int $year): array {
    return vf_all('SELECT z.*,COALESCE(SUM(a.betrag_cent),0) AS verwendet_cent FROM vs_zahlungen z LEFT JOIN vs_zuordnungen a ON a.zahlung_id=z.id WHERE z.mitglied_id=? AND z.gezahlt_am BETWEEN ? AND ? GROUP BY z.id ORDER BY z.gezahlt_am DESC,z.id DESC',[$member,$year.'-01-01',$year.'-12-31']);
}
function vf_totals(int $member,int $year): array {
    $r=vf_one('SELECT COALESCE(SUM(betrag_cent),0) AS soll FROM vs_forderungen WHERE mitglied_id=? AND storniert_am IS NULL AND periode BETWEEN ? AND ?',[$member,$year.'-01',$year.'-12']);
    $a=vf_one('SELECT COALESCE(SUM(a.betrag_cent),0) AS ist FROM vs_zuordnungen a JOIN vs_forderungen f ON f.id=a.forderung_id JOIN vs_zahlungen z ON z.id=a.zahlung_id WHERE f.mitglied_id=? AND f.periode BETWEEN ? AND ? AND f.storniert_am IS NULL AND z.storniert_am IS NULL',[$member,$year.'-01',$year.'-12']);
    $credit=vf_one('SELECT COALESCE(SUM(betrag_cent),0) AS eingang FROM vs_zahlungen WHERE mitglied_id=? AND storniert_am IS NULL',[$member]);
    $used=vf_one('SELECT COALESCE(SUM(a.betrag_cent),0) AS verwendet FROM vs_zuordnungen a JOIN vs_zahlungen z ON z.id=a.zahlung_id WHERE z.mitglied_id=? AND z.storniert_am IS NULL',[$member]);
    return ['soll'=>(int)$r['soll'],'ist'=>(int)$a['ist'],'offen'=>max(0,(int)$r['soll']-(int)$a['ist']),'guthaben'=>max(0,(int)$credit['eingang']-(int)$used['verwendet'])];
}

/** Zuordnung pro Buchung sichtbar für eine prüfbare Belegspur. */
function vf_allocations_for_year(int $member,int $year): array {
    $rows=vf_all('SELECT a.zahlung_id,f.periode,f.beschreibung,a.betrag_cent FROM vs_zuordnungen a JOIN vs_forderungen f ON f.id=a.forderung_id JOIN vs_zahlungen z ON z.id=a.zahlung_id WHERE z.mitglied_id=? AND z.gezahlt_am BETWEEN ? AND ? ORDER BY a.zahlung_id,f.periode,f.id',[$member,$year.'-01-01',$year.'-12-31']);
    $group=[];foreach($rows as $r)$group[(int)$r['zahlung_id']][]=$r;return $group;
}