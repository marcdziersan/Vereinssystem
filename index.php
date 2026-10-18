<?php
// Datenbankverbindung & Kodierung einbinden
require_once("inc/connect.inc.php");

// Login-Status-Prüfung einbinden
require_once("inc/checkuser.inc.php");

// HTML-Kopf einbinden
require_once("inc/head.inc.php");
?>
<div id="content">
<section class="vs-dashboard-intro" aria-labelledby="vs-dashboard-title">
  <div><span class="vs-eyebrow">MEIN VEREIN · ÜBERSICHT</span>
    <h1 id="vs-dashboard-title">Guten Tag, <?= vui_escape($user['vorname'] ?? '') ?>.</h1>
    <p>Dein Verein auf einen Blick. Alle wichtigen Bereiche sind mit einem Klick erreichbar.</p>
  </div>
  <div class="vs-quicklinks" aria-label="Schnellzugriff">
    <a href="members.php"><?= vui_icon('users',19) ?> Mitglieder</a>
    <a href="veranstaltungen.php"><?= vui_icon('calendar',19) ?> Veranstaltungen</a>
    <a href="<?= (int)($user['rights'] ?? 0) >= 4 ? 'finanzen.php' : 'finanzkonto.php' ?>"><?= vui_icon('chart',19) ?> Finanzen</a>
    <?php if (function_exists('vk_can') && vk_can($user, 'kasse.ansehen')): ?><a href="kasse.php"><?= vui_icon('wallet',19) ?> Kasse</a><?php endif; ?>
  </div>
</section>
    <div class="boxsystem33">
        <div class="leftbox">
            <h2>Was heute ansteht</h2>
            <?php
            if (($_SESSION["freshlogin"])) {
                // Ist der Benutzer neu eingeloggt, dann Willkommensmeldung anzeigen
                echo "<div class=\"message success\"><p>";
                if ($user['lastlogintime'] == 0) {
                    // Ist dies der erste Login, kann kein letzter Besuch angezeigt werden
                    echo "Herzlich Willkommen, Sie haben sich erfolgreich angemeldet.<br />Dies ist ihr erster Besuch!";
                } else {
                    echo "Willkommen zurück, Sie haben sich erfolgreich angemeldet.<br />Ihr letzter Besuch war am " . vs_strftime("%A, dem %d. %B %Y, um %H:%M", $user['lastlogintime']) . " Uhr.";
                }
                echo "</p></div>";
                // Session-Variable auf false setzen, um zu kennzeichnen, dass der Nutzer nicht mehr frisch eingeloggt ist
                $_SESSION["freshlogin"] = false;
            }
            // Kommende Dienste des Nutzers auslesen
            $sql_nextdienst = "SELECT `veranstaltungen`.*,`dienste`.`stand`,`dienste`.`dienstart`,MIN(`dienste`.`startzeit`) as anfang,MAX(`dienste`.`endzeit`) as ende, COUNT(`dienst_id`) as dienstzahl FROM `dienste` LEFT JOIN `veranstaltungen` ON `veranstaltungen`.`veranstaltungs_id` = `dienste`.`event` WHERE `dienste`.`person` = " . $user['mitglieder_id'] . " AND `dienste`.`startzeit` > UNIX_TIMESTAMP() GROUP BY `dienste`.`stand` ORDER BY `dienste`.`startzeit` ASC, `dienste`.`endzeit` ASC LIMIT 0,2";
            $nextdienste_result = db_query($sql_nextdienst);
            if (db_num_rows($nextdienste_result) > 0) {
                echo "<div class=\"message information\"><p>Ihr";
                if (db_num_rows($nextdienste_result) == 1) {
                    echo " nächster Dienst bei einer Veranstaltung ist:</p>";
                } else {
                    echo "e nächsten Dienste bei Veranstaltungen sind:</p>";
                }
                while ($dienst = db_fetch_assoc($nextdienste_result)) {
                    echo "<p>";
                    if ($dienst['dienstart'] > 0) {
                        echo "<b>" . $dienst['dienstart'] . " Kuchen mitbringen</b> bei <a href=\"veranstaltung_show.php?id=" . $dienst['veranstaltungs_id'] . "\" title=\"Veranstaltung anzeigen\">" . $dienst['veranstaltungsname'] . "</a> am " . date("d.m.Y", $dienst['startzeit']);
                    } else {
                        $dienstarten = explode("\n", $dienst['dienstbeschreibung']);
                        $dienstbeschr = "";
                        if ($dienst['stand'] == 10) {
                            $dienstbeschr = trim(substr($dienstarten[9], 3));
                        } else {
                            $dienstbeschr = trim(substr($dienstarten[$dienst['stand'] - 1], 2));
                        }
                        if (empty($dienstbeschr)) {
                            echo "Standdienst";
                        } else {
                            echo htmlspecialchars($dienstbeschr);
                        }
                        echo " bei <a href=\"veranstaltung_show.php?id=" . $dienst['veranstaltungs_id'] . "\" title=\"Veranstaltung anzeigen\">" . $dienst['veranstaltungsname'] . "</a> am <b>" . date("d.m.Y", $dienst['anfang']);
                        // Wie lange dauert es vom Beginn des ersten Dienstes bis zum letzten?
                        $dauer = ($dienst["ende"] - $dienst["anfang"]) / 60;
                        if ((45 + ($dienst['dienstzahl'] - 1) * 30) < $dauer) {
                            // Es werden nicht alle Dienste an diesem Stand am Stück gemacht, die Anfangs- und Endzeit in der Abfrage beschreiben also keine durchgehende Schicht
                            echo " ab " . date("H:i", $dienst["anfang"]) . " Uhr";
                        } else {
                            // Es wird eine durchgehende Schicht an diesem Stand gemacht, anfang und ende beschreiben also den Beginn und das Ende der Schicht
                            if (date("d", $dienst["anfang"]) == date("d", $dienst["ende"])) {
                                // Dienst endet am selben Tag, wie begonnen hat (nicht über Mitternacht)
                                echo " von " . date("H:i", $dienst["anfang"]) . " bis " . date("H:i", $dienst["ende"]) . " Uhr";
                            } else {
                                echo " von " . date("H:i", $dienst["anfang"]) . " bis " . date("d.m.Y", $dienst['anfang']) . " um " . date("H:i", $dienst["ende"]) . " Uhr";
                            }
                        }
                        echo "</b>";
                    }
                    echo "</p>";
                }
                echo "</div>";
            }
            //Alle Dienste der betroffenen Person raussuchen
            /* $sql_dienste = "SELECT * FROM `dienste` WHERE `person` = ".$user['mitglieder_id'];
              $dienste_result = db_query($sql_dienste);
              //Dienstzähler zurücksetzen
              $kuchen_jahr = 0;
              $kuchen = 0;
              $dienste_jahr = 0;
              $dienste = 0;

              $i = 0;
              $kommend = FALSE;
              //Wurden Dienste gefunde?
              if($dienste_result != FALSE and db_num_rows($dienste_result) != 0){
              while($dienst = db_fetch_assoc($dienste_result)){
              //Nächsten Dienst herraussuchen
              //Nur kommende Dienste durchsuchen
              if($dienst['startzeit'] - time() > 0){
              if($i == 0 or ($kommend['startzeit'] > $dienst['startzeit'])){
              $kommend = $dienst;
              }
              }
              //Je nach Datum dem zugehörigen Zähler zuaddieren
              if(date("Y", $dienst['startzeit']) == date("Y", time())){
              if($dienst['dienstart'] > 0){
              $kuchen_jahr++;
              $kuchen++;
              }else{
              $dienste_jahr++;
              $dienste++;
              }
              }else{
              if($dienst['dienstart'] > 0){
              $kuchen++;
              }else{
              $dienste++;
              }
              }
              }
              $i++;
              }
              if($kommend != FALSE){
              $sql_veranstaltung = "SELECT * FROM `veranstaltungen` WHERE `veranstaltungs_id` = ".$kommend['event'];
              $veranstaltung_result = db_query($sql_veranstaltung);
              if($veranstaltung_result != FALSE and db_num_rows($veranstaltung_result) != 0){
              $veranstaltung = db_fetch_assoc($veranstaltung_result);
              if (date("d.n.Y", $kommend["startzeit"]) == date("d.n.Y", $kommend["endzeit"])) {
              $termin = date("d.n.Y", $kommend["startzeit"]) . "<br />Von " . date("H:i", $kommend["startzeit"]) . " bis " . date("H:i", $kommend["endzeit"]);
              } else {
              $termin = date("d.n.Y", $kommend["startzeit"]) . ", " . date("H:i", $kommend["startzeit"]) . " bis <br />" . date("d.n.Y", $kommend["endzeit"]) . ", " . date("H:i", $kommend["endzeit"]);
              }
              echo "<div class=\"message information\"><p>Ihr nächster Dienst auf einer Veranstaltung ist:<br />";
              if($kommend['dienstart'] > 0){
              echo "<b>".$kommend['dienstart']." Kuchen mitbringen bei ".$veranstaltung['veranstaltungsname']." am ".date("d.m.y", $kommend['startzeit']);
              }else{
              echo "<b>Standdienst bei ".$veranstaltung['veranstaltungsname']." am ".$termin;
              }
              echo "</b></p></div>";
              }
              }
             */
            ?>
            <h2>Häufige Aktionen</h2>
            <table class="noborder tcenter freqactiontable">
                <colgroup>
                    <col width="25%" />
                    <col width="25%" />
                    <col width="25%" />
                    <col width="25%" />
                </colgroup>
                <tr>
                    <td><a class="blockdisplay" href="veranstaltungen.php?time=neu" title="Kommende Veranstaltungen anzeigen"><img src="images/calendar.png" alt="" /><br />Kommende Veranstaltungen</a></td>
                    <td><a class="blockdisplay" href="member_edit.php?id=<?php echo $user['mitglieder_id']; ?>" title="Eigenes Profil bearbeiten"><img src="images/edit.png" alt="" /><br />Profil bearbeiten</a></td>
                    <td><a class="blockdisplay" href="member_changepword.php" title="Eigenes Passwort ändern"><img src="images/password.png" alt="" /><br />Passwort ändern</a></td>
                    <td><?php
                        if ($user['rights'] >= 2) {
                            echo "<a class=\"blockdisplay\" href=\"news_add.php\" title=\"Neue Nachricht schreiben\"><img src=\"images/add.png\" alt=\"\" /><br />Nachricht schreiben</a>";
                        } else {
                            echo "<a class=\"blockdisplay\" href=\"ort_show.php?id=" . $settings['vereinsanschrift'] . "\" title=\"Adresse der Geschäftsstelle anzeigen\"><img src=\"images/location.png\" alt=\"\" /><br />Geschäftsstelle finden</a>";
                        }
                        ?></td>
                </tr>
            </table>
            <div class="clearit">&nbsp;</div>
            <h2>Letzte Nachrichten</h2>
            <?php
            $sql = "SELECT * FROM `news` LEFT JOIN `mitglieder` ON `news`.`author` = `mitglieder`.`mitglieder_id` WHERE `news`.`status` = 1 AND (`minright` <= '" . $user['rights'] . "' OR `author` = '" . $user['mitglieder_id'] . "') ORDER BY `lastchange` DESC, `created` DESC LIMIT 0,3";
            $nachrichten = db_query($sql);
            if (db_num_rows($nachrichten) == 0) {
                echo "<p>Es wurden keine Nachrichten gefunden!</p>";
            } else {
                while ($nachricht = db_fetch_assoc($nachrichten)) {
                    echo "<h3 class=\"clearit\">" . $nachricht['title'] . "</h3>\n
                            <p>" . htmlspecialchars(substr(strip_tags((string)$nachricht['content']), 0, 165), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "... <a class=\"floatright\" href=\"news_show.php?id=" . $nachricht['news_id'] . "\" title=\"Nachricht komplett anzeigen\">Weiterlesen</a></p>";
                }
                echo "<p><img src=\"images/arrow_right.png\" alt=\"\" class=\"imageinline\" /> <a href=\"news.php\" title=\"Zur Nachrichtenübersicht\">Zur Nachrichtenübersicht</a></p>";
            }
            ?>
        </div>
        <div class="rightbox">
            <?php
            include("inc/action_leiste_index.inc.php");
            ?>
        </div>
    </div>
    <div class="clearit">&nbsp;</div>
</div>
<?php
// HTML-Fußbereich einbinden
require_once("inc/footer.inc.php");
?>