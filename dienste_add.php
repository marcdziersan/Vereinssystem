<?php
// Datenbankverbindung & Kodierung einbinden
require_once ("inc/connect.inc.php");

// Login-Status-Prüfung einbinden
require_once ("inc/checkuser.inc.php");

if(isset($_GET['data'])){
	$input = json_decode(base64_decode($_GET['data']), TRUE);
	$data = $input;
}else{
	$input = $_GET;
}
if(isset($_GET['errors'])){
	$errors = json_decode(base64_decode($_GET['errors']));
}else{
	$errors = "";
}

if(!(((isset($input['anfang']) and isset($input['ende']) and isset($input['stand'])) or isset($input['kuchen']) or isset($input['dienstart'])) and isset($input['event']) and ($user['rights'] > 3 or ($user['rights'] == 3 and ($veranstaltung['startzeit'] - time()) > 259200) or $user['mitglieder_id'] == $veranstaltung['ansprechpartner']))){
	header('Location: veranstaltungen.php');
	exit();
}


// Titel festlegen
$title = "Dienst hinzufügen";

// JavaScript einfügen
$appendjs = "$(\"#vorname, #nachname\").change(function(){
      $(\"#other\").attr(\"checked\",\"checked\");
});
";

// HTML-Kopf einbinden
require_once ("inc/head.inc.php");
?>
<div id="content">
	<div class="boxsystem33">
		<div class="leftbox">
			<h1>Neuer Dienst</h1>
					<?php
					$sql = "SELECT * FROM `veranstaltungen` WHERE `veranstaltungs_id` = ".intval($input['event']);
					$veranstaltung_result = db_query($sql);
  				    if (db_error() !== '') error_log('[Vereinssystem/SQL] ' . db_error());
  				    if (db_num_rows($veranstaltung_result) == 1){
    				    $veranstaltung = db_fetch_assoc($veranstaltung_result);
						 ?>
  				    	<p><img src="images/arrow_left.png" alt="" title="Anzeigen" /> <a href="dienste_show.php?id=<?php echo $veranstaltung['veranstaltungs_id']; ?>" title="Die Dienstdetails von <?php echo htmlspecialchars($veranstaltung['veranstaltungsname'])?> anzeigen">Dienstdetails anzeigen</a></p>
    				   <?php
						if (isset($_GET['action'])) {
							if ($_GET['action'] == "fail") {
								echo "<div class=\"message error\">Beim Versuch, die Veranstaltung zu speichern, sind leider Fehler aufgetreten.";
								if (is_array($errors) && count($errors) > 0) {
									//Fehlerarray anzeigen
									echo "<br />Folgende inhaltliche Fehler sind festgestellt worden:<br /><b>" . (implode("<br />", $errors)) . "</b></div>";
									//Falls bereits eingegebene Daten vorhanden sind, werden diese decodiert
									if (isset($input['data'])) {
										$data = json_decode(base64_decode($input['data']), TRUE);
									}
								} else {
									// Vermutlich ein Fehler bei der Kommunikation mit der MySQL Datenbank. $input['why'] enthält die MySQL-Fehlernummer
									echo "<div class=\"message error\">Beim Versuch, die Änderungen zu speichern, ist leider ein Fehler aufgetreten. Bitte versuchen Sie es erneut oder wenden sich an den Systemadministrator.</div>";
								}
							}
						}?>
					<form id="addform" class="formular" method="post" action="dienste_process.php" accept-charset="utf-8">
                    <input type="hidden" name="vs_csrf" value="<?php echo htmlspecialchars($_SESSION['vs_csrf'],ENT_QUOTES); ?>" />
					<p>
					<label for="selber">Person</label>
				    <input type="radio" name="person" value="self" id="selber" <?php if((isset($data['person']) and $data['person'] == "self") or !isset($data['person'])){echo "checked=\"checked\"";}?> /> Ich (<?php echo $user['vorname']. " ".$user['nachname'];?>)<br />
    				</p>
    				<p>
    				<label for="other">&nbsp;</label>
    				<input type="radio" name="person" value="other" id="other" <?php if(isset($data['person']) and $data['person'] == "other"){echo "checked=\"checked\"";}?> />

					<?php
					//Falls kein Vor und Nachname bei doppelten Datenbankeinträgen übergeben wurde, einfachen Namen in Textbox schreiben
					if (empty($data['more_persons'])) {
					?>
						Vorname
						<input type="text" name="vorname" id="vorname" class="xsmall" value="<?php if (isset($data['vorname'])) {echo htmlspecialchars($data['vorname']);}?>" />
						- Nachname
						<input type="text" name="nachname" id="nachname" class="xsmall" value="<?php if (isset($data['nachname'])) {echo htmlspecialchars($data['nachname']);}?>" />
						<?php
					} else {
						//Ansonsten alle Personen mit dem gleichen Namen suchen und mit deren Daten verknüpfen
						$sql_personen = "SELECT * FROM `mitglieder` LEFT JOIN `orte` ON `mitglieder`.`anschrift` = `orte`.`orts_id` WHERE `vorname` = '" . db_escape(vs_sanitize_text($data['vorname'])) . "' AND `nachname` = '" . db_escape(vs_sanitize_text($data['nachname'])) . "'";
						$personen_result = db_query($sql_personen);
						//Verfügbare Personen mit Wohnort in Selectbox schreiben
						echo '<select size="1" name="ansprechpartner_id" id="ansprechpartner_id" class="xlarge">';
						while ($person = db_fetch_assoc($personen_result)) {
							echo "<option value=\"" . $person['mitglieder_id'] . "\">" . $person['vorname'] . " " . $person['nachname'] . "  (" . $person['plz'] . " " . $person['ort'] . ")" . "</option>";
						}
						echo "</select>";
					}
					?></p>
					<p>
					<label for="dienstart">Dienstart</label>
						<select size="1" name="dienstart" id="dienstart" class="medium" disabled="disabled">
							<option value="0">Standdienst</option>
							<option value="kuchen" <?php if(isset($input['kuchen']) or (isset($data['dienstart']) and $data['dienstart'] == 0)){echo "selected=\"selected\"";}?>>Kuchendienst</option>
						</select>
					</p>
					<?php
					if(isset($input['anfang']) and isset($input['ende']) and isset($input['stand'])){?>
					<p>
					<label for="zeit">Zeit</label>
						<?php
						if(isset($veranstaltung['dienstbeschreibung'])){
							$dienstbeschreibung = explode("\n",$veranstaltung['dienstbeschreibung']);
						}
						if(isset($dienstbeschreibung[$input['stand']-1]) and strlen($dienstbeschreibung[$input['stand']-1]) > 4){
							$stand = htmlspecialchars($dienstbeschreibung[$input['stand']-1]);
						}else{
							$stand = $input['stand'];
						}

						if(isset($input['anfang']) and isset($input['ende']) and isset($input['stand'])){echo "<b>".date("d.m.Y H:i", $input['anfang'])."</b> bis <b>".date("d.m.Y H:i", $input['ende'])."</b> an <b>Stand ".$stand."</b>";}?>
					</p><br />
					<input type="hidden" name="anfang" value="<?php if(isset($input['anfang'])){echo $input['anfang'];} ?>" />
					<input type="hidden" name="ende" value="<?php if(isset($input['ende'])){echo $input['ende'];} ?>" />
					<input type="hidden" name="stand" value="<?php if(isset($input['stand'])){echo $input['stand'];} ?>" />
					<?php }elseif(isset($input['kuchen'])){?>
					<p>
					<label for="kuchenanzahl">Kuchenanzahl</label>
					<input type="text" name="kuchen" id="kuchenanzahl" class="xsmall" value="<?php if(isset($data['kuchen'])){echo $data['kuchen'];}else{echo "1";}?>"/>
					</p>
					<?php }?>
					<input type="hidden" name="event" value="<?php echo $veranstaltung['veranstaltungs_id']; ?>" />
					<input type="hidden" name="action" value="add" />
					<input type="submit" name="savedienst" value="Speichern" title="Speichern" class="button medium" />
					</form>
			<h2>Kuchenliste</h2>
			<?php
				$sql_dienste = "SELECT * FROM `dienste` LEFT JOIN `mitglieder` ON `dienste`.`person` = `mitglieder`.`mitglieder_id` WHERE `dienstart` > 0 AND `event` = " . intval($_GET['event']);
				$dienste_result = db_query($sql_dienste); ?>
				<table>
                  <?php
                  if(db_num_rows($dienste_result) != 0){
                  ?>
                  <thead>
                    <tr>
                        <th>Name</th>
                        <th>Menge</th>
                        <th>Aktionen</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                   while ($dienst = db_fetch_assoc($dienste_result)) {
                   		echo '<tr><td>'.htmlspecialchars($dienst['vorname'].' '.$dienst['nachname']).'</td><td>'.(int)$dienst['dienstart'].'</td><td>'.vs_action_form(['action'=>'remove','dienst_id'=>$dienst['dienst_id'],'event'=>$dienst['event']], 'Dienst löschen').'</td></tr>';
				   }
				  }else{
				  	echo "<tbody><tr><td>Keine Kuchendienste zu dieser Veranstaltung eingetragen.</td></tr>";
				  }
				   ?>

                  </tbody>
				</table>
		</div>
		<div class="rightbox">
		  <?php
		include ("inc/action_leiste_events.inc.php");
		  ?>
		</div>
	</div>
	<?php
	}else{
	 ?>
	<p class="message error">Es wurde keine Veranstaltung mit dieser ID gefunden!<br /><a href="veranstaltungen.php" title="Übersicht aller Veranstaltungen anzeigen">Zur Veranstaltungsverwaltung</a></p>

    <?php } ?>
	<div class="clearit">&nbsp;</div>
</div>

<?php
// HTML-Fußbereich einbinden
require_once ("inc/footer.inc.php");
?>
