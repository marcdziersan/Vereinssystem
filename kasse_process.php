<?php
/**
 * Vereinssystem 2.3.1 – Modul: kasse process.
 * Pfad: kasse_process.php
 * Historische Ablauflogik von 2013, behutsam für PHP 8/PDO weiterentwickelt.
 * Änderungen an der Originalfunktionalität sind in CHANGELOG.md dokumentiert.
 */
declare(strict_types=1);
/** Schreibendpunkte der Kassenverwaltung. Nur POST, CSRF und Rechtekontrolle. */
require_once __DIR__ . '/inc/connect.inc.php';
require_once __DIR__ . '/inc/checkuser.inc.php';
require_once __DIR__ . '/inc/cash.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405); exit('Nur POST-Anfragen zugelassen.');
}
// Einmalige Formularsignatur verhindert unabsichtliche Doppelbuchungen durch Doppelklick.
$nonce = is_string($_POST['vk_nonce'] ?? null) ? $_POST['vk_nonce'] : '';
if (empty($_SESSION['vk_nonce']) || $nonce==='' || !hash_equals((string)$_SESSION['vk_nonce'],$nonce)) {
    http_response_code(409); exit('Formular bereits verarbeitet oder abgelaufen. Bitte Seite neu laden.');
}
unset($_SESSION['vk_nonce']);
$action = (string)($_POST['action'] ?? '');
$actor = (int)$user['mitglieder_id'];
$redirect = in_array($action, ['permission','rate'],true) ? 'kasse_admin.php' : 'kasse.php';

try {
    if (!vk_ready()) throw new DomainException('Bitte zuerst upgrade.php als Administrator ausführen.');
    switch ($action) {
        case 'create_account':
            vk_require($user,'kasse.konten');
            vk_create_account((string)($_POST['name'] ?? ''), (string)($_POST['typ'] ?? ''),
                (string)($_POST['anfang'] ?? '0'), (string)($_POST['datum'] ?? ''), $actor);
            $notice = 'Konto angelegt.'; break;
        case 'edit_account':
            vk_require($user,'kasse.konten');
            vk_set_account((int)($_POST['id'] ?? 0), (string)($_POST['name'] ?? ''),
                (string)($_POST['typ'] ?? ''), isset($_POST['aktiv']), $actor);
            $notice = 'Konto aktualisiert.'; break;
        case 'book':
            vk_require($user,'kasse.buchen');
            $id = vk_book((int)($_POST['konto'] ?? 0), (string)($_POST['art'] ?? ''),
                (string)($_POST['betrag'] ?? ''), (string)($_POST['datum'] ?? ''),
                (string)($_POST['kategorie'] ?? ''), (string)($_POST['beschreibung'] ?? ''),
                (string)($_POST['belegnummer'] ?? ''), $actor);
            $notice = 'Buchung #' . $id . ' gespeichert.'; break;
        case 'transfer':
            vk_require($user,'kasse.buchen');
            vk_transfer((int)($_POST['von'] ?? 0), (int)($_POST['nach'] ?? 0),
                (string)($_POST['betrag'] ?? ''), (string)($_POST['datum'] ?? ''),
                (string)($_POST['beschreibung'] ?? ''), $actor);
            $notice = 'Umbuchung auf beiden Konten erfasst.'; break;
        case 'void':
            vk_require($user,'kasse.storno');
            vk_void((int)($_POST['id'] ?? 0), (string)($_POST['grund'] ?? ''), $actor);
            $notice = 'Buchung bzw. Umbuchung nachvollziehbar storniert.'; break;
        case 'rate':
            vk_require($user,'beitraege.tarife');
            vk_set_rate((string)($_POST['art'] ?? ''), (int)($_POST['abteilung'] ?? 0),
                (string)($_POST['gueltig_ab'] ?? ''), (string)($_POST['betrag'] ?? ''), $actor);
            $notice = 'Neuer Beitragssatz angelegt. Bereits erzeugte Forderungen bleiben unverändert.'; break;
        case 'permission':
            if ((int)$user['rights'] !== 5) throw new DomainException('Rechte darf nur ein Administrator mit Level 5 vergeben.');
            $member=(int)($_POST['mitglied']??0);
            $right=(string)($_POST['recht']??'');
            if ($member <= 0 || !array_key_exists($right,VK_RECHTE)) throw new DomainException('Mitglied oder Berechtigung ungültig.');
            if (!vf_one('SELECT 1 AS ok FROM mitglieder WHERE mitglieder_id=? AND status=1',[$member])) throw new DomainException('Mitglied nicht aktiv.');
            $pdo=vf_db();$pdo->beginTransaction();
            try {
                if (isset($_POST['gewähren'])) {
                    vf_exec('INSERT INTO vs_berechtigungen (mitglied_id,recht,vergeben_von) VALUES (?,?,?) ON DUPLICATE KEY UPDATE vergeben_von=VALUES(vergeben_von),vergeben_am=NOW()',[$member,$right,$actor]);
                    $operation='vergeben';
                } else {
                    vf_exec('DELETE FROM vs_berechtigungen WHERE mitglied_id=? AND recht=?',[$member,$right]);
                    $operation='entzogen';
                }
                vk_audit($actor,'rechte.'.$operation,'mitglied',$member,['recht'=>$right]);
                $pdo->commit();
            } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
            $notice='Berechtigung '.$operation.'.';break;
        default:
            throw new DomainException('Unbekannte Aktion.');
    }
    $_SESSION['vk_flash']=['ok',$notice];
} catch (Throwable $error) {
    error_log('[Vereinssystem/Kassenaktion] '. $error->getMessage());
    $_SESSION['vk_flash']=['error',$error instanceof DomainException ? $error->getMessage() : 'Buchung fehlgeschlagen. Bitte Serverprotokoll prüfen.'];
}
header('Location: '.$redirect, true, 303);
exit;
