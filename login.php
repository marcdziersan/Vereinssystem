<?php
// Datenbankverbindung & Kodierung einbinden
require_once("inc/connect.inc.php");

// Sicherer Session-Start (auch für den Captcha-Text und CSRF-Schutz)
vs_session_start();
if (empty($_SESSION['vs_login_token'])) $_SESSION['vs_login_token'] = bin2hex(random_bytes(32));
if ((int)$settings['usecaptchalogin'] === 1) $_SESSION['vs_captcha'] = strtoupper(substr(bin2hex(random_bytes(4)),0,4));
// HTML-Kopf
require_once("inc/head.inc.php");
?>
        <div id="content">
    		<div class="boxsystem33">
    		  <div class="leftbox">
                  <h2>Anmeldung</h2>
                  <?php
                  if (isset($_GET['fail'])){
                    if ($_GET['fail'] === 'wait') {
                      echo '<div class="message warning"><p>Zu viele Versuche. Bitte später erneut anmelden.</p></div>';
                    } elseif ($_GET['fail'] == "captcha"){
                      echo "<div class=\"message error\"><p>Der eingegebene Anti-Spam Code war nicht korrekt.</p></div>";
                    }elseif ($_GET['fail'] == "cookie"){
                      echo "<div class=\"message warning\"><p>Ihr Browser akzeptiert keine Cookies, welche zum Login erforderlich sind.</p></div>";
                    }else{
                      echo "<div class=\"message error\"><p>Login fehlgeschlagen. Bitte überprüfen Sie die eingegebenen Anmelde-Daten!</p></div>";
                    }
                  }elseif(isset($_GET['loggedout'])){
                    echo "<div class=\"message success\"><p>Sie haben sich erfolgreich abgemeldet.</p></div>";
                  }elseif(isset($_GET['old'])){
                    echo "<div class=\"message information\"><p>Ihre Sitzung ist nicht mehr gültig. Bitte melden Sie sich erneut an.</p></div>";
                  }elseif(isset($_GET['forbidden'])){
                    echo "<div class=\"message error\"><p>Ihr Nutzeraccount wurde gesperrt oder ist noch nicht freigschaltet. Bitte wenden Sie sich an den System-Administrator.</p></div>";
                  }
                  ?>
                    <form id="loginform" class="formular" method="post" action="login_process.php" accept-charset="utf-8">
                        <input type="hidden" name="vs_login_token" value="<?php echo htmlspecialchars($_SESSION['vs_login_token'], ENT_QUOTES); ?>" />
                        <p>
                            <label for="username">Nutzername/Mitgliedsnr.</label>
                            <input type="text" name="username" id="username" title="Ihr Benutzername oder Ihre Mitgliedsnummer" class="medium" />
                        </p>
                        <p>
                            <label for="psword">Passwort</label>
                            <input type="password" name="psword" id="psword" title="Ihr Passwort" class="medium"/>
                        </p>
                        <?php if ((int)$settings['usecaptchalogin'] === 1): ?>
                        <p><img src="captcha.php" alt="Sicherheitscode" width="180" height="58" /></p>
                        <p><label for="captcha">Sicherheitscode</label>
                        <input type="text" name="captcha" id="captcha" maxlength="4" autocomplete="off" class="medium" required /></p>
                        <?php endif; ?>
                        <p>
                            <?php
                            if (isset($_GET['after'])){
                              // Ist eine after-URL übergeben und diese ist eine URL dieses Servers
                              echo "<input type=\"hidden\" name=\"after\" value=\"".htmlspecialchars((string)$_GET['after'], ENT_QUOTES)."\" />";
                            }
                            ?>
                            <input type="submit" name="Login" value="Login" title="Einloggen" class="button medium" />
                        </p>
                    </form>
              </div>
    		  <div class="rightbox">
                  <h2>Warum Anmelden?</h2>
                  <p>Da die Daten im Vereinssystem vetraulich sind, ist es nur ausgewiesenen Mitgliedern erlaubt, diese einzusehen. <br />Nach Ihrer Anmeldung können Sie sich für Dienste bei Veranstaltungen eintragen, ihre bisherigen Arbeiten einsehen und Ihre Mitgliedschaft bearbeiten.</p>
    		  </div>
    		</div>
    		<div class="clearit">&nbsp;</div>
        </div>
<?php
// HTML-Fußbereich einbinden
require_once("inc/footer.inc.php");
?>