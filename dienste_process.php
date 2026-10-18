<?php
// Verarbeitet Speicher- und Erstellungsprozesse von Mitgliedern

// Datenbankverbindung & Kodierung einbinden
require_once ("inc/connect.inc.php");
//echo "stop";
// Login-Status-Pr�fung einbinden
require_once ("inc/checkuser.inc.php");
if (isset($_POST['action']) and isset($_POST['event'])) {
	//Error Counter auf False setzen
	$errors = FALSE;
	//Betroffene Veranstaltung aus der DB abrufen
	$sql_veranstaltung = "SELECT * FROM `veranstaltungen` WHERE `veranstaltungs_id` = " . intval($_POST['event']);
	$veranstaltung_result = db_query($sql_veranstaltung);
	//Nur fortfahren wenn es genau ein Ergebniss gibt
	if (db_num_rows($veranstaltung_result) == 1) {

		$veranstaltung = db_fetch_assoc($veranstaltung_result);
		//Soll eine Veranstaltung erstellt oder gelöscht werden?
		if ($_POST['action'] == "add") {
            $person = (int)$user['mitglieder_id']; // auch ohne explizite Person-Auswahl
            if (isset($_POST['kuchen']) && (!ctype_digit((string)$_POST['kuchen']) || (int)$_POST['kuchen'] > 100)) {
                header('Location: dienste_show.php?action=fail&id=' . intval($_POST['event'])); exit();
            }

											if(isset($_POST['person']) and $_POST['person'] == "other"){
												if(empty($_POST['ansprechpartner_id'])){
												//Variable die angibt ob es mehrere Personen desselben Namens gibt
												$more_persons = FALSE;


													//Textfelder für Ansprechpartner sind ausgefüllt
													if (!empty($_POST['vorname']) and !empty($_POST['nachname'])) {
														//Test ob Ansprechpartner in der DB vorhanden

														$sql_test_person = "SELECT * FROM `mitglieder` WHERE `vorname` = '" . db_escape(vs_sanitize_text($_POST['vorname'])) . "' AND `nachname` = '" . db_escape(vs_sanitize_text($_POST['nachname'])) . "'";
															$test_person_result = db_query($sql_test_person);

															$personen = db_fetch_assoc($test_person_result);

															//echo db_error();
															//Wenn es nur eine Person mit diesem Namen gibt oder diese Person bereits in der DB steht
															if (db_num_rows($test_person_result) == 1) {
																$person = $personen['mitglieder_id'];
															} elseif (db_num_rows($test_person_result) > 1) {
																//Wenn es mehrere Personen gibt muss dies im nächsten Schritt ausgewählt werden
																$more_persons = TRUE;
																$errors[] = "Es wurden mehrere Personen mit diesem Namen gefunden, bitte w&auml;hlen sie die richtige aus!";
															} else {
																$errors[] = "Keine Person mit diesem Namen in der Datenbank gefunden";
															}
														} else {
															$errors[] = "Ansprechpartner muss vorhanden sein";
														}

													if ($more_persons == TRUE) {
														$data = $_POST;
														$data = array_merge($data, array("more_persons" => "true"));
													}
													if(isset($_POST['kuchen'])){
														$get = "";
													}else{

													}
													if($errors != FALSE){
														header('Location: dienste_add.php?action=fail&event=' . intval($_POST['event']) . '&data=' . base64_encode(json_encode($data)) . '&errors=' . base64_encode(json_encode($errors)));
														exit();
													}
												  }else{
												  	$person = $_POST['ansprechpartner_id'];
												  }
												}elseif(isset($_POST['person']) and $_POST['person'] == "self"){
													$person = $user['mitglieder_id'];
												}





			//Personen die entweder die berechtigung besitzen oder ansprechpartner sind und auch eine entsprechende Person eingeben dürfen die Veranstaltung bearbeiten
			if ($user['rights'] > 3 or ($user['rights'] == 3 and ($veranstaltung['startzeit'] - time()) > 259200) or $user['mitglieder_id'] == $veranstaltung['ansprechpartner']) {
				$admin = TRUE;
			} else {
				$person = $user['mitglieder_id'];
				$admin = FALSE;
			}
			//Geht es um Kuchen
			if (isset($_POST['kuchen'])) {
				$sql_dienste = "SELECT * FROM `dienste` WHERE `dienstart` > 0 AND `event` = " . intval($_POST['event']);
				$dienste_result = db_query($sql_dienste);

				$belegt = FALSE;
				//Überprüfen ob es bereits einen Kuchendienst für diese Person gibt
				while ($dienst = db_fetch_assoc($dienste_result)) {
					if ($dienst['person'] == $person) {
						$belegt = $dienst['dienst_id'];
						break;
					}
				}
				if (($veranstaltung['startzeit'] - time()) > 259200 or $admin == TRUE) {
					//Wurde die Kuchenanzahl auf 0 gesetzt?
					if ($_POST['kuchen'] != 0) {
						if (!$belegt) {
							//neuen Dienst erzeugen
							$sql = "INSERT INTO `dienste` SET
		        	 				`event` = '" . db_escape(vs_sanitize_text($_POST['event'])) . "',
		        	 				`dienstart` = '" . db_escape(vs_sanitize_text($_POST['kuchen'])) . "',
		        	 				`startzeit` = '" . db_escape(vs_sanitize_text($veranstaltung['startzeit'])) . "',
		        	 				`person` = '" . $person . "'";
						} elseif ($belegt != $_POST['kuchen']) {
							//Bereits bestehenden Dienst updaten
							$sql = "UPDATE `dienste` SET
		        	 				`dienstart` = '" . db_escape(vs_sanitize_text($_POST['kuchen'])) . "'
		        	 			     WHERE `dienst_id` = '" . intval($belegt) . "'";
						}
						if (isset($sql) and db_query($sql)) {
							// Bei Erfolg entsprechendes als GET-Parameter übergeben
							header('Location: dienste_show.php?action=success&id=' . intval($_POST['event']));
							exit();
						} else {
							// Bei einem Fehler die Mysql-Fehlernummer zur Fehleruntersuchung mitsenden
							header('Location: dienste_show.php?action=fail&id=' . intval($_POST['event']));
							exit();
						}
					} else {
						//Wenn es 0 Kuchen geben soll, wird der entweder der vorhandene Dienst gelöscht oder gar keiner angelegt
						if (!$belegt) {
							//An Dienstseite ohne Aktion leiten
							header('Location: dienste_show.php?id=' . intval($_POST['event']));
							exit();
						} else {
							//An diese Seite leiten mit Löschparameter
							$permission = ($admin === TRUE) ? '' : ' AND `person` = '.intval($user['mitglieder_id']);
                            db_query('DELETE FROM `dienste` WHERE `dienst_id` = '.intval($belegt).' AND `event` = '.intval($_POST['event']).$permission);
                            header('Location: dienste_show.php?action=success&id='.intval($_POST['event']));
                            exit();
						}
					}
				} else {
					header('Location: dienste_show.php?action=fail&id=' . intval($_POST['event']));
					exit();
				}
			} else {
				$sql_dienste = "SELECT * FROM `dienste` WHERE `event` = " . intval($_POST['event']);
				$dienste_result = db_query($sql_dienste);

				$belegt = FALSE;
				while ($dienst = db_fetch_assoc($dienste_result)) {
					if ($dienst['startzeit'] <= $_POST['anfang'] and $dienst['endzeit'] >= $_POST['ende'] and $dienst['person'] == $person and $dienst['dienstart'] == 0) {
						$belegt = TRUE;
						break;
					}
				}
				//Dienstzeit muss innerhalb der Veranstaltung sein und nicht länger als 45 Minuten
				if ((!$belegt and isset($_POST['stand']) and isset($_POST['anfang']) and isset($_POST['ende']) and $_POST['anfang'] < $_POST['ende'] and $_POST['anfang'] >= ($veranstaltung['startzeit'] - 30 * 60) and $_POST['ende'] <= ($veranstaltung['endzeit'] + 30 * 60) and ($_POST['ende'] - $_POST['anfang']) <= (45 * 60)) and (($veranstaltung['startzeit'] - time()) > 259200 or $admin == TRUE)) {

					$sql = "INSERT INTO `dienste` SET
	        	 				`event` = '" . db_escape(vs_sanitize_text($_POST['event'])) . "',
	        	 				`startzeit` = '" . db_escape(vs_sanitize_text($_POST['anfang'])) . "',
	        	 				`endzeit` = '" . db_escape(vs_sanitize_text($_POST['ende'])) . "',
	        	 				`stand` = '" . db_escape(vs_sanitize_text($_POST['stand'])) . "',
	        	 				`dienstart` = '0',
	        	 				`person` = '" . $person . "'";
					if (db_query($sql)) {
						// Bei Erfolg entsprechendes als GET-Parameter übergeben
						header('Location: dienste_show.php?action=success&id=' . intval($_POST['event']));
						exit();
					} else {
						// Bei einem Fehler die Mysql-Fehlernummer zur Fehleruntersuchung mitsenden
						header('Location: dienste_show.php?action=fail&id=' . intval($_POST['event']) . '&why=' . db_errno());
						exit();
					}

				} else {
					header('Location: dienste_show.php?action=fail&id=' . intval($_POST['event']));
					exit();
				}
			}

		} elseif ($_POST['action'] == "remove" and ($user['rights'] > 3 or ($user['rights'] <= 3 and ($veranstaltung['startzeit'] - time()) > 259200) or $user['mitglieder_id'] == $veranstaltung['ansprechpartner'])) {
			if (isset($_POST['dienst_id'])) {
				$sql_dienste = "SELECT * FROM `dienste` WHERE `dienst_id` = " . intval($_POST['dienst_id']);
				$dienste_result = db_query($sql_dienste);
				$dienst = db_fetch_assoc($dienste_result);

					// Ohne Verwaltungsrechte nur eigene Dienste löschen.
                    if ((int)$user['rights'] < 4 && (int)$user['mitglieder_id'] !== (int)$veranstaltung['ansprechpartner'] && (int)($dienst['person'] ?? 0) !== (int)$user['mitglieder_id']) {
                        http_response_code(403); exit('Keine Berechtigung.');
                    }
                    $sql = "DELETE FROM `dienste` WHERE `dienst_id` = '" . intval($_POST['dienst_id']) . "' AND `event` = '" . intval($_POST['event']) . "'";
					if (db_query($sql)) {
						// Bei Erfolg entsprechendes als GET-Parameter übergeben
						header('Location: dienste_show.php?action=success&id=' . intval($_POST['event']));
						exit();
					} else {
						// Bei einem Fehler die Mysql-Fehlernummer zur Fehleruntersuchung mitsenden
						header('Location: dienste_show.php?action=fail&id=' . intval($_POST['event']) . '&why=' . db_errno());
						exit();
					}

			}
		} else {
			header('Location: dienste_show.php?action=fail&id=' . intval($_POST['event']));
			exit();
		}
	} else {
		// Weiterleitung, falls aus irgendwelchen Gr�nden diese Datei aufgerufen wird, ohne, dass etwas �bergeben wird
		header('Location: veranstaltungen.php');
	}
} else {
	// Weiterleitung, falls aus irgendwelchen Gr�nden diese Datei aufgerufen wird, ohne, dass etwas �bergeben wird
	header('Location: veranstaltungen.php');
}
?>