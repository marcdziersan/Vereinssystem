<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
      <title><?php if (isset($title)){
        echo htmlspecialchars($title)." - ";
      }
      if (!empty($settings['vereinsname'])){
        echo htmlspecialchars((string)$settings['vereinsname'],ENT_QUOTES)." - ";
      }
      echo "VereinsSystem";
      ?></title>
      <meta name="description" content="Vereinssystem, Informatik-Abitur 2012 Projekt" />
      <meta name="author" content="Nicolas Jourdan, Sebastian Fuss" />
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
      <link rel="stylesheet" type="text/css" href="css/main.css" />
      <?php if (in_array(basename($_SERVER["SCRIPT_NAME"] ?? ""), ["finanzen.php","finanzkonto.php","kasse.php","kasse_rechte.php","kasse_tarife.php","kasse_import.php","kasse_protokoll.php"], true)): ?>
      <link rel="stylesheet" href="css/finance.css?v=2.3.1">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <?php endif; ?>
      <?php if (str_starts_with(basename($_SERVER["SCRIPT_NAME"] ?? ""), "kasse")): ?>
      <link rel="stylesheet" href="css/cash.css?v=2.3.1">
      <?php endif; ?>
      <link rel="icon" href="images/favicon.ico" type="image/x-icon" />
      <link rel="apple-touch-icon" href="images/apple-touch-icon.png" />
      <!--[if IE]>
      <style type="text/css">
          /* IE Hacks */
          body{
              text-align: center;
          }
          #main{
              text-align: left;
          }
          #footer{
              width: 980px;
          }
      </style>
      <![endif]-->
	  <script type="text/javascript" src="assets/js/jquery-3.6.1.min.js"></script>
      <script type="text/javascript">
	    $(document).ready(function() {
	      <?php
          if (isset($appendjs)){
            echo $appendjs;
          }
          ?>
		});
      </script>
  </head>
  <body>
	<div id="main">
		<div id="header" class="textcenter">
            <a href="index.php"><img src="images/logo_head.png" title="VereinsSystem" alt="VereinsSystem" /></a>
		</div>
        <?php if (isset($user) && is_array($user) && !function_exists('vk_can')) {
            require_once __DIR__ . '/cash.php';
        } ?>
        <div id="navi">

        <?php
        if (isset($_SESSION['uid'])){
            // Haben wir einen angemeldeten Nutzer? Nur dann den Menu-Inhalt anzeigen
        ?>
            <ul>
                <li><a href="index.php" title="Startseite">Home</a></li>
                <li><a href="members.php" title="Mitgliederübersicht">Mitglieder</a></li>
                <li><a href="abteilungen.php" title="Abteilungen">Abteilungen</a></li>
                <li><a href="orte.php" title="Orte">Orte</a></li>
                <li><a href="veranstaltungen.php" title="Veranstaltungen">Veranstaltungen</a></li>
                <li><a href="<?php echo (int)$user['rights'] >= 4 ? 'finanzen.php' : 'finanzkonto.php'; ?>" title="Beitragskonto">Finanzen</a></li>
                <?php if (function_exists('vk_can') && vk_can($user,'kasse.ansehen')): ?>
                  <li><a href="kasse.php" title="Kassenbuch">Kasse</a></li>
                <?php endif; ?>
                <?php
                if ($user['rights'] >= 5){
                  echo "<li><a href=\"einstellungen.php\" title=\"Einstellungen\">Einstellungen</a></li>\n";
                }
                ?>
                <li class="logoutlink"><a href="logout.php" title="Abmelden und aktuelle Sitzung beenden">Logout <?php echo htmlspecialchars($user['vorname']." ".$user['nachname']);?></a></li>
            </ul>
        <?php
        }
        ?>
        </div>
        <noscript>
        <p class="message warning"><b>Bitte aktivieren Sie JavaScript</b>, um diese Seite uneingeschränkt nutzen zu können!</p>
        </noscript>