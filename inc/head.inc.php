<?php
/** Gemeinsame, vollständig responsive Oberfläche aller historischen und neuen Module. */
require_once __DIR__.'/ui.php';
$file = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
[$pageName,$pageGroup] = vui_page($file);
$authenticated = isset($user) && is_array($user) && !empty($_SESSION['uid']);
$club = trim((string) ($settings['vereinsname'] ?? 'Vereinssystem')) ?: 'Vereinssystem';
$displayName = $authenticated ? trim((string) ($user['vorname'] ?? '') . ' ' . (string) ($user['nachname'] ?? '')) : '';
$isAdmin = $authenticated && (int) ($user['rights'] ?? 0) >= 5;
if ($authenticated && !function_exists('vk_can')) require_once __DIR__.'/cash.php';
$canCash = $authenticated && function_exists('vk_can') && vk_can($user,'kasse.ansehen');
$financeLink = $authenticated && (int) ($user['rights'] ?? 0) >= 4 ? 'finanzen.php' : 'finanzkonto.php';
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="#101f32">
  <meta name="description" content="Vereinssystem – Mitglieder, Veranstaltungen, Beiträge und Kassenführung.">
  <meta name="author" content="Nicolas Jourdan, Sebastian Fuss, Marcus Dziersan">
  <title><?= vui_escape(isset($title) && (string)$title !== '' ? $title : $pageName) ?> · <?= vui_escape($club) ?></title>
  <link rel="icon" href="images/favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="css/finance.css">
  <link rel="stylesheet" href="css/cash.css">
  <link rel="stylesheet" href="css/app.css?v=2.4.0">
  <script src="assets/js/jquery-3.6.1.min.js" defer></script>
  <script src="assets/js/app.js?v=2.4.0" defer></script>
  <?php if (!empty($appendjs)): ?>
  <!-- Alte Module befüllen $appendjs mit jQuery-Ausdrücken. Deferred jQuery
       ist vor DOMContentLoaded bereit; Eventhandler bleiben funktionsfähig. -->
  <script>document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery) { jQuery(function ($) { <?= $appendjs ?> }); }
  });</script>
  <?php endif; ?>
</head>
<body class="vs-app<?= $authenticated ? ' vs-authenticated' : ' vs-public' ?>" data-page="<?= vui_escape($pageGroup) ?>">
<a class="vs-skip" href="#content">Zum Hauptinhalt springen</a>
<div id="main" class="vs-shell">
  <?php if ($authenticated): ?>
  <header id="header" class="vs-topbar">
    <button type="button" class="vs-icon-button vs-menu-toggle" data-vs-menu aria-label="Menü öffnen" aria-controls="navi" aria-expanded="false"><?= vui_icon('menu',23) ?></button>
    <a class="vs-topbar-brand" href="index.php" aria-label="Zur Übersicht"><img src="images/logo_head.png" alt="Vereinssystem"></a>
    <div class="vs-crumb"><span>Vereinsverwaltung</span><span class="vs-crumb-arrow">›</span><strong><?= vui_escape($pageName) ?></strong></div>
    <div class="vs-topbar-actions">
      <button type="button" class="vs-icon-button" data-vs-theme aria-label="Farbschema wechseln" title="Farbschema wechseln"><?= vui_icon('moon',19) ?></button>
      <a href="member_show.php?id=<?= (int) ($user['mitglieder_id'] ?? 0) ?>" class="vs-user-pill" title="Eigenes Profil ansehen"><span class="vs-avatar"><?= vui_escape((function_exists('mb_strtoupper') ? mb_strtoupper(mb_substr((string) ($user['vorname'] ?? 'M'),0,1)) : strtoupper(substr((string) ($user['vorname'] ?? 'M'),0,1)))) ?></span><span class="vs-user-label"><?= vui_escape($displayName) ?></span></a>
    </div>
  </header>
  <div class="vs-nav-scrim" data-vs-close hidden></div>
  <nav id="navi" class="vs-sidebar" aria-label="Hauptnavigation" data-open="false">
    <div class="vs-sidebar-brand"><img src="images/logo_head.png" alt="Vereinssystem"><button type="button" class="vs-icon-button vs-menu-close" data-vs-close aria-label="Menü schließen"><?= vui_icon('close',21) ?></button></div>
    <div class="vs-sidebar-caption" title="<?= vui_escape($club) ?>"><?= vui_escape($club) ?></div>
    <span class="vs-nav-heading">VEREIN</span>
    <ul class="vs-nav-list">
      <?= vui_navlink('index.php','Übersicht','home','start',$pageGroup) ?>
      <?= vui_navlink('members.php','Mitglieder','users','members',$pageGroup) ?>
      <?= vui_navlink('abteilungen.php','Abteilungen','layers','departments',$pageGroup) ?>
      <?= vui_navlink('orte.php','Orte & Anschriften','pin','locations',$pageGroup) ?>
      <?= vui_navlink('veranstaltungen.php','Veranstaltungen & Dienste','calendar','events',$pageGroup) ?>
      <?= vui_navlink('news.php','Nachrichten','news','news',$pageGroup) ?>
    </ul>
    <span class="vs-nav-heading">VERWALTUNG</span>
    <ul class="vs-nav-list">
      <?= vui_navlink($financeLink,'Beiträge & Finanzen','chart','finances',$pageGroup) ?>
      <?php if($canCash): ?><?= vui_navlink('kasse.php','Kassenverwaltung','wallet','cash',$pageGroup) ?><?php endif; ?>
      <?php if($isAdmin): ?><?= vui_navlink('einstellungen.php','Einstellungen','settings','settings',$pageGroup) ?><?php endif; ?>
    </ul>
    <div class="vs-sidebar-bottom"><div class="vs-account-mini"><span class="vs-avatar"><?= vui_escape((function_exists('mb_strtoupper') ? mb_strtoupper(mb_substr((string) ($user['vorname'] ?? 'M'),0,1)) : strtoupper(substr((string) ($user['vorname'] ?? 'M'),0,1)))) ?></span><span><strong><?= vui_escape($displayName) ?></strong><small><?= $isAdmin ? 'Administration' : 'Mitgliedskonto' ?></small></span></div><a class="vs-logout" href="logout.php"><?= vui_icon('logout',19) ?><span>Abmelden</span></a></div>
  </nav>
  <nav class="vs-mobile-tabs" aria-label="Mobile Schnellnavigation">
    <a href="index.php"<?= $pageGroup === 'start' ? ' aria-current="page"' : '' ?>><?= vui_icon('home',21) ?><span>Start</span></a>
    <a href="members.php"<?= $pageGroup === 'members' ? ' aria-current="page"' : '' ?>><?= vui_icon('users',21) ?><span>Mitglieder</span></a>
    <a href="veranstaltungen.php"<?= $pageGroup === 'events' ? ' aria-current="page"' : '' ?>><?= vui_icon('calendar',21) ?><span>Termine</span></a>
    <a href="<?= vui_escape($financeLink) ?>"<?= $pageGroup === 'finances' ? ' aria-current="page"' : '' ?>><?= vui_icon('chart',21) ?><span>Beiträge</span></a>
    <button type="button" data-vs-menu aria-controls="navi" aria-expanded="false" aria-label="Alle Bereiche öffnen"><?= vui_icon('menu',21) ?><span>Mehr</span></button>
  </nav>
  <?php else: ?>
  <header id="header" class="vs-public-header"><a href="login.php" class="vs-public-brand"><img src="images/logo_head.png" alt="Vereinssystem"></a><span class="vs-public-label">Vereinsverwaltung</span><button type="button" class="vs-icon-button" data-vs-theme aria-label="Farbschema wechseln"><?= vui_icon('moon',20) ?></button></header>
  <?php endif; ?>
  <noscript><style>@media(max-width:1099px){body.vs-app #navi.vs-sidebar{position:static;visibility:visible;transform:none;width:100%;max-width:none;height:auto;grid-row:auto}body.vs-app .vs-menu-toggle{display:none}}</style><div class="vs-nojs" role="note">Ohne JavaScript sind Navigation und Formulare weiterhin verfügbar.</div></noscript>