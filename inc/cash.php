<?php
/**
 * Vereinssystem 2.3.0 – Modul: cash.
 * Pfad: inc/cash.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/**
 * Vereinssystem 2.3.0 – Kassenbuch, Berechtigungen und Prüfspur.
 *
 * Alle Beträge sind ganzzahlige Cent (kein float). Schreibvorgänge erfolgen
 * transaktional auf derselben PDO-Verbindung wie die Beitragsverwaltung.
 * Bereits gespeicherte Buchungen werden nicht physisch gelöscht.
 *
 * @copyright 2026 Marcus Dziersan (eigene Ergänzungen)
 * @license PolyForm-Noncommercial-1.0.0 (siehe LICENSE.md)
 */
require_once __DIR__ . '/finance.php';

/** Erlaubte Kassenaktionen. Admin-Level 5 hat alle Rechte. */
const VK_RECHTE = [
    'kasse.ansehen' => 'Kassenbestände und Buchungen ansehen',
    'kasse.buchen' => 'Einnahmen, Ausgaben und Umbuchungen buchen',
    'kasse.konten' => 'Konten anlegen, umbenennen und deaktivieren',
    'kasse.storno' => 'Kassenbuchungen nachvollziehbar stornieren',
    'kasse.import' => 'CSV-Kassenbuchungen importieren',
    'kasse.export' => 'Berichte und CSV/PDF exportieren',
    'kasse.belege' => 'Belege hochladen und abrufen',
    'kasse.pruefung' => 'Änderungsprotokoll ansehen',
    'beitraege.tarife' => 'Zukünftige Beitragssätze festlegen',
];

/** Werden alle zusätzlichen Tabellen unterstützt? */
function vk_ready(): bool
{
    static $ready = null;
    if ($ready !== null) return $ready;
    try {
        $row = vf_one("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('vs_konten','vs_kassenbuch','vs_belege','vs_berechtigungen','vs_audit','vs_tarife','vs_schema_version')");
        return $ready = ((int)($row['n'] ?? 0) === 7 && vf_ready());
    } catch (Throwable $error) {
        error_log('[Vereinssystem Kasse] ' . $error->getMessage());
        return false;
    }
}

/** Normale Verwaltungsrechte sind separat vom historischen numerischen Level. */
function vk_can(array $user, string $permission): bool
{
    if (!array_key_exists($permission, VK_RECHTE)) return false;
    if ((int)($user['rights'] ?? 0) >= 5) return true;
    if (!vk_ready()) return false;
    return vf_one('SELECT 1 AS ok FROM vs_berechtigungen WHERE mitglied_id=? AND recht=?', [
        (int)$user['mitglieder_id'], $permission,
    ]) !== null;
}

function vk_require(array $user, string $permission): void
{
    if (!vk_can($user, $permission)) {
        http_response_code(403);
        throw new DomainException('Keine Berechtigung: ' . (VK_RECHTE[$permission] ?? $permission));
    }
}

/** Jede Änderung wird innerhalb derselben Transaktion wie die Buchung erfasst. */
function vk_audit(int $actor, string $action, string $object, int $id, array $data = []): void
{
    vf_exec('INSERT INTO vs_audit (akteur,aktion,objekt,objekt_id,details) VALUES (?,?,?,?,?)', [
        $actor, $action, $object, $id,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    ]);
}

/** Auch negative Anfangsbestände sind für reale Kontoüberziehungen möglich. */
function vk_parse_signed(string $value): int
{
    $value = trim($value);
    $negative = str_starts_with($value, '-');
    $cent = vf_parse_amount($negative ? substr($value, 1) : $value);
    return $negative ? -$cent : $cent;
}

/** Vierstellige Nachkommastellen oder Tausendertrennzeichen werden abgelehnt. */
function vk_amount(string $value): int
{
    return vf_parse_amount($value);
}

function vk_text(mixed $value, int $max, bool $required = false): string
{
    $value = trim((string)$value);
    if (($required && $value === '') || mb_strlen_safe($value) > $max || str_contains($value, "\0")) {
        throw new DomainException('Ein Pflichttext fehlt oder ist zu lang (max. ' . $max . ' Zeichen).');
    }
    return $value;
}

function mb_strlen_safe(string $text): int
{
    return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
}

/** Sperrt ein existierendes Konto. Inaktive Konten dürfen weiter ausgewertet werden. */
function vk_account(int $id, bool $lock = false, bool $active = false): array
{
    $account = vf_one('SELECT * FROM vs_konten WHERE id=?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    if (!$account || ($active && (int)$account['aktiv'] !== 1)) {
        throw new DomainException('Konto nicht vorhanden oder nicht aktiv.');
    }
    return $account;
}

function vk_accounts(bool $includeInactive = false): array
{
    return vf_all('SELECT * FROM vs_konten' . ($includeInactive ? '' : ' WHERE aktiv=1') . ' ORDER BY aktiv DESC,name');
}

/** Konto anlegen: Anfangsbestand wird getrennt von laufenden Umsätzen geführt. */
function vk_create_account(string $name, string $type, string $opening, string $date, int $actor): int
{
    $name = vk_text($name, 120, true);
    $type = vk_text($type, 40, true); vf_date($date);
    $openingCent = trim($opening) === '0' || trim($opening) === '0,00' ? 0 : vk_parse_signed($opening);
    $pdo = vf_db(); $pdo->beginTransaction();
    try {
        vf_exec('INSERT INTO vs_konten (name,typ,anfangsbestand_cent,anfangsdatum,erstellt_von) VALUES (?,?,?,?,?)', [
            $name, $type, $openingCent, $date, $actor,
        ]);
        $id = (int)$pdo->lastInsertId();
        vk_audit($actor, 'konto.erstellt', 'konto', $id, ['name'=>$name, 'typ'=>$type, 'anfang_cent'=>$openingCent]);
        $pdo->commit(); return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function vk_set_account(int $id, string $name, string $type, bool $active, int $actor): void
{
    $pdo = vf_db(); $pdo->beginTransaction();
    try {
        $old = vk_account($id, true);
        vf_exec('UPDATE vs_konten SET name=?,typ=?,aktiv=? WHERE id=?', [
            vk_text($name, 120, true), vk_text($type, 40, true), (int)$active, $id,
        ]);
        vk_audit($actor, 'konto.geaendert', 'konto', $id, ['alt'=>$old['name'], 'neu'=>$name, 'aktiv'=>$active]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

/** Ein Datensatz ist ein Eingang oder Ausgang auf einem konkreten Konto. */
function vk_insert_locked(int $account, string $kind, string $date, int $cent,
                          string $category, string $description, string $receipt,
                          int $actor, ?int $paymentId = null, ?string $transferKey = null,
                          ?string $importKey = null): int
{
    if (!in_array($kind, ['einnahme','ausgabe','umbuchung_ein','umbuchung_aus'], true) || $cent <= 0) {
        throw new DomainException('Ungültiger Buchungstyp oder Geldbetrag.');
    }
    $row = vk_account($account, false, true);
    if ($date < $row['anfangsdatum']) throw new DomainException('Buchungsdatum liegt vor dem Anfangsbestand.');
    vf_date($date);
    vf_exec('INSERT INTO vs_kassenbuch (konto_id,art,datum,betrag_cent,kategorie,beschreibung,belegnummer,zahlung_id,umbuchung_key,import_key,erstellt_von) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [
        $account, $kind, $date, $cent, $category, $description, $receipt,
        $paymentId, $transferKey, $importKey, $actor,
    ]);
    return (int)vf_db()->lastInsertId();
}

function vk_book(int $account, string $kind, string $amount, string $date,
                 string $category, string $description, string $receipt, int $actor,
                 ?string $importKey = null): int
{
    if (!in_array($kind, ['einnahme','ausgabe'], true)) throw new DomainException('Nur Einnahme oder Ausgabe erlaubt.');
    $cent = vk_amount($amount); vf_date($date);
    $category = vk_text($category, 100); $description = vk_text($description, 500, true);
    $receipt = vk_text($receipt, 100);
    $pdo = vf_db(); $pdo->beginTransaction();
    try {
        vk_account($account, true, true);
        $id = vk_insert_locked($account, $kind, $date, $cent, $category, $description, $receipt, $actor, null, null, $importKey);
        vk_audit($actor, 'kasse.' . $kind, 'buchung', $id, ['konto'=>$account, 'betrag_cent'=>$cent, 'datum'=>$date]);
        $pdo->commit(); return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

/** Umbuchung: zwei gleich hohe Gegenbuchungen, atomar oder gar nicht. */
function vk_transfer(int $from, int $to, string $amount, string $date, string $note, int $actor): array
{
    if ($from === $to || $from <= 0 || $to <= 0) throw new DomainException('Verschiedene Quell- und Zielkonten wählen.');
    $cent = vk_amount($amount); vf_date($date); $note = vk_text($note, 500, true);
    $pdo = vf_db(); $pdo->beginTransaction();
    try {
        // Immer in identischer Reihenfolge sperren, reduziert Deadlock-Risiko.
        foreach ([min($from,$to), max($from,$to)] as $id) vk_account($id, true, true);
        $key = bin2hex(random_bytes(16));
        $out = vk_insert_locked($from, 'umbuchung_aus', $date, $cent, 'Umbuchung', $note, '', $actor, null, $key);
        $in = vk_insert_locked($to, 'umbuchung_ein', $date, $cent, 'Umbuchung', $note, '', $actor, null, $key);
        vk_audit($actor, 'kasse.umbuchung', 'buchung', $out, ['eingang_id'=>$in, 'quelle'=>$from, 'ziel'=>$to, 'betrag_cent'=>$cent]);
        $pdo->commit(); return [$out, $in];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

/**
 * Verknüpft einen echten Beitragseingang einmalig mit einem Kassenkonto.
 * Aufruf AUSSCHLIESSLICH innerhalb der laufenden vf_add_payment()-Transaktion.
 */
function vk_link_payment_locked(int $accountId, int $paymentId, int $memberId,
                                int $cent, string $date, int $actor): void
{
    if (!vf_db()->inTransaction()) throw new LogicException('Beitragsbuchung braucht eine offene Transaktion.');
    vk_account($accountId, true, true);
    $id = vk_insert_locked($accountId, 'einnahme', $date, $cent, 'Mitgliedsbeitrag',
        'Mitgliedsbeitrag Mitglied #' . $memberId, 'BEITRAG-' . $paymentId,
        $actor, $paymentId);
    vk_audit($actor, 'beitrag.kasse', 'buchung', $id, ['zahlung_id'=>$paymentId]);
}

/** Beiträge-Storno und Kassen-Storno müssen gemeinsam committen oder rollbacken. */
function vk_void_linked_payment_locked(int $paymentId, int $actor, string $reason): void
{
    $row = vf_one('SELECT id FROM vs_kassenbuch WHERE zahlung_id=? AND storniert_am IS NULL FOR UPDATE', [$paymentId]);
    if ($row) {
        vf_exec('UPDATE vs_kassenbuch SET storniert_am=NOW(),storniert_von=?,storno_grund=? WHERE id=?', [$actor,$reason,$row['id']]);
        vk_audit($actor, 'beitrag.kasse.storno', 'buchung', (int)$row['id'], ['zahlung_id'=>$paymentId, 'grund'=>$reason]);
    }
}

/** Buchungen bleiben historisch erhalten, zählen nach Storno aber nicht mehr zum Saldo. */
function vk_void(int $id, string $reason, int $actor): void
{
    $reason = vk_text($reason, 250, true);
    if (mb_strlen_safe($reason) < 5) throw new DomainException('Stornogrund: mindestens 5 Zeichen.');
    $pdo = vf_db(); $pdo->beginTransaction();
    try {
        $entry = vf_one('SELECT * FROM vs_kassenbuch WHERE id=? FOR UPDATE', [$id]);
        if (!$entry || $entry['storniert_am'] !== null) throw new DomainException('Buchung nicht vorhanden oder bereits storniert.');
        if ($entry['zahlung_id'] !== null) throw new DomainException('Beitragseingänge nur im Beitragskonto stornieren.');
        $entries = [$entry];
        if ($entry['umbuchung_key'] !== null) {
            $entries = vf_all('SELECT * FROM vs_kassenbuch WHERE umbuchung_key=? FOR UPDATE', [$entry['umbuchung_key']]);
            if (count($entries)!==2) throw new DomainException('Umbuchung ist unvollständig; manuelle Prüfung erforderlich.');
            foreach ($entries as $part) if ($part['storniert_am'] !== null) throw new DomainException('Umbuchung ist bereits (teilweise) storniert.');
        }
        foreach ($entries as $part) {
            vf_exec('UPDATE vs_kassenbuch SET storniert_am=NOW(),storniert_von=?,storno_grund=? WHERE id=?', [$actor,$reason,$part['id']]);
            vk_audit($actor, 'kasse.storno', 'buchung', (int)$part['id'], ['grund'=>$reason]);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

/** Jahres-Summen einschließlich Anfangsbestand zum Periodenende. */
function vk_balances(int $year, ?int $accountId = null): array
{
    $accounts = $accountId ? [vk_account($accountId)] : vk_accounts(true);
    $result = [];
    foreach ($accounts as $account) {
        $row = vf_one("SELECT
          COALESCE(SUM(CASE WHEN art IN ('einnahme','umbuchung_ein') THEN betrag_cent ELSE -betrag_cent END),0) AS delta,
          COALESCE(SUM(CASE WHEN art='einnahme' AND datum BETWEEN ? AND ? THEN betrag_cent ELSE 0 END),0) AS einnahmen,
          COALESCE(SUM(CASE WHEN art='ausgabe' AND datum BETWEEN ? AND ? THEN betrag_cent ELSE 0 END),0) AS ausgaben,
          COALESCE(SUM(CASE WHEN art='umbuchung_ein' AND datum BETWEEN ? AND ? THEN betrag_cent ELSE 0 END),0) AS umbuchung_ein,
          COALESCE(SUM(CASE WHEN art='umbuchung_aus' AND datum BETWEEN ? AND ? THEN betrag_cent ELSE 0 END),0) AS umbuchung_aus
          FROM vs_kassenbuch WHERE konto_id=? AND storniert_am IS NULL AND datum<=?", [
            "$year-01-01","$year-12-31", "$year-01-01","$year-12-31",
            "$year-01-01","$year-12-31", "$year-01-01","$year-12-31", $account['id'], "$year-12-31",
        ]);
        $opened = $account['anfangsdatum'] <= "$year-12-31";
        $result[] = $account + [
            'saldo_cent' => ($opened ? (int)$account['anfangsbestand_cent'] : 0) + (int)$row['delta'],
            'einnahmen_cent'=>(int)$row['einnahmen'], 'ausgaben_cent'=>(int)$row['ausgaben'],
            'umbuchung_ein_cent'=>(int)$row['umbuchung_ein'], 'umbuchung_aus_cent'=>(int)$row['umbuchung_aus'],
        ];
    }
    return $result;
}

function vk_entries(int $year, ?int $accountId = null, int $limit = 250): array
{
    $query = 'SELECT b.*, k.name AS kontoname FROM vs_kassenbuch b JOIN vs_konten k ON k.id=b.konto_id WHERE b.datum BETWEEN ? AND ?';
    $params = ["$year-01-01", "$year-12-31"];
    if ($accountId) { $query .= ' AND b.konto_id=?'; $params[] = $accountId; }
    // LIMIT stammt ausschließlich aus einem int und kann deshalb nicht injiziert werden.
    return vf_all($query . ' ORDER BY b.datum DESC,b.id DESC LIMIT ' . max(1,min(10000,$limit)), $params);
}

/** Beitragsänderungen sind append-only: gültig ab Stichtag, alte Sollstellungen bleiben fest. */
function vk_set_rate(string $type, int $department, string $date, string $amount, int $actor): int
{
    if (!in_array($type, ['kind','erwachsen','abteilung'], true)) throw new DomainException('Beitragsart ungültig.');
    if ($type!=='abteilung' && $department!==0 || $type==='abteilung' && $department<=0) throw new DomainException('Abteilung / Beitragsart widersprüchlich.');
    vf_date($date); $cent=in_array(trim($amount),['0','0,00','0.00'],true)?0:vk_amount($amount);
    $pdo=vf_db();$pdo->beginTransaction();
    try {
        if ($type==='abteilung' && !vf_one('SELECT abteilungs_id FROM abteilungen WHERE abteilungs_id=?',[$department])) throw new DomainException('Abteilung existiert nicht.');
        vf_exec('INSERT INTO vs_tarife (art,abteilung_id,gueltig_ab,betrag_cent,erstellt_von) VALUES (?,?,?,?,?)',[$type,$department,$date,$cent,$actor]);
        $id=(int)$pdo->lastInsertId();
        vk_audit($actor,'beitrag.tarif.erstellt','tarif',$id,['art'=>$type,'abteilung'=>$department,'ab'=>$date,'betrag_cent'=>$cent]);
        $pdo->commit();return $id;
    }catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
}

/** Ab dem ersten hinterlegten Tarif nie auf spätere Sätze zurückfallen. */
function vk_rate(string $type, int $department, string $date, int $fallback): int
{
    if (!vk_ready()) return $fallback;
    $row = vf_one('SELECT betrag_cent FROM vs_tarife WHERE art=? AND abteilung_id=? AND gueltig_ab<=? ORDER BY gueltig_ab DESC LIMIT 1', [$type,$department,$date]);
    if ($row !== null) return (int)$row['betrag_cent'];
    // Vor dem ersten festgelegten Stichtag gilt der alte Beitragssatz.
    return $fallback;
}