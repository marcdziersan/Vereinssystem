<?php
/** Mobile-first member ledger. All user-originated data is escaped for HTML. */
declare(strict_types=1);
use Vereinssystem\Core\View as V;
use Vereinssystem\Finance\Money;
$member=$ledger['member'];$totals=$ledger['totals'];
$memberName=trim((string)$member['vorname'].' '.(string)$member['nachname']);
$target=static fn(string $path):string=>$path.'&member='.$memberId.'&year='.$year;
?>
<section class="vs-section vs-section--first vs-finance" aria-labelledby="finance-title">
  <div class="vs-toolbar"><div><p class="vs-kicker">Beitragswesen · Soll und Ist</p><h1 id="finance-title">Beitragskonto</h1>
  <p class="vs-muted"><?= V::escape($memberName) ?> · Mitglied #<?= (int)$memberId ?> · Jahr <?= (int)$year ?></p></div>
  <?php if($canGenerate): ?><a class="vs-button" href="<?= V::escape($tariffsUrl) ?>">Beitragssätze</a><?php endif; ?></div>
  <?php if($flash!==''):?><div class="vs-alert" role="status"><?= V::escape($flash) ?></div><?php endif; ?>
  <!-- Query filters are GET-only; changing a year never modifies a booking. -->
  <form method="get" action="<?= V::escape($url) ?>" class="vs-searchbar">
    <input type="hidden" name="r" value="/finance">
    <?php if($canManage):?><label class="vs-field"><span class="vs-label">Mitglieds-ID</span><input class="vs-input" name="member" type="number" min="1" required value="<?= (int)$memberId ?>"></label><?php endif; ?>
    <label class="vs-field"><span class="vs-label">Beitragsjahr</span><input class="vs-input" name="year" type="number" min="2000" max="2099" value="<?= (int)$year ?>"></label>
    <button class="vs-button" type="submit">Anzeigen</button>
  </form>
  <div class="vs-finance-stats" aria-label="Beitragsübersicht">
   <?php foreach(['soll'=>'Soll','ist'=>'Bezahlt','offen'=>'Offen','guthaben'=>'Guthaben gesamt'] as $key=>$label):?>
     <div class="vs-card vs-finance-stat"><span class="vs-muted"><?= V::escape($label) ?></span><strong><?= V::escape(Money::format((int)$totals[$key])) ?></strong></div>
   <?php endforeach; ?>
  </div>
  <p class="vs-muted vs-finance-note">Soll, Ist und Offen beziehen sich auf <?= (int)$year ?>. Guthaben ist der nicht zugeordnete Betrag über alle Jahre.</p>
  <!-- Forderungen bleiben auch nach einem Storno sichtbar (revisionsfähige Historie). -->
  <section class="vs-section"><h2>Forderungen</h2>
   <?php if(!$ledger['claims']):?><div class="vs-card">Für dieses Jahr liegen keine Forderungen vor.</div><?php endif; ?>
   <div class="vs-finance-grid">
   <?php foreach($ledger['claims'] as $c):$void=$c['storniert_am']!==null; ?>
     <article class="vs-card vs-finance-entry"><div class="vs-finance-entry__head"><h3><?= V::escape($c['beschreibung']) ?></h3><span class="vs-chip vs-chip--outline"><?= $void?'Storniert':'Aktiv' ?></span></div>
       <p class="vs-muted"><?= V::escape($c['periode']) ?> · Fällig <?= V::escape(date('d.m.Y',strtotime($c['faellig_am']))) ?> · #<?= (int)$c['id'] ?></p>
       <dl class="vs-finance-values"><div><dt>Soll</dt><dd><?= V::escape(Money::format((int)$c['betrag_cent'])) ?></dd></div><div><dt>Ist</dt><dd><?= V::escape(Money::format($void?0:(int)$c['bezahlt_cent'])) ?></dd></div>
       <div><dt>Offen</dt><dd><?= V::escape(Money::format($void?0:max(0,(int)$c['betrag_cent']-(int)$c['bezahlt_cent']))) ?></dd></div></dl>
       <?php if($canVoid && !$void && (int)$c['bezahlt_cent']===0):?>
       <form class="vs-finance-inline" action="<?= V::escape($voidClaimUrl) ?>" method="post"><input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="member" value="<?= (int)$memberId ?>"><input type="hidden" name="year" value="<?= (int)$year ?>"><label class="vs-field"><span class="vs-label">Stornogrund</span><input class="vs-input" name="reason" required minlength="5" maxlength="250"></label><button class="vs-button" type="submit">Forderung stornieren</button></form>
       <?php endif; ?>
     </article>
   <?php endforeach;?></div>
  </section>
  <!-- Zahlungseingänge werden nie gelöscht; ein Storno deaktiviert ihre Zuordnungen. -->
  <section class="vs-section"><h2>Zahlungen</h2>
   <?php if(!$ledger['payments']):?><div class="vs-card">Für dieses Jahr liegen keine Zahlungen vor.</div><?php endif; ?>
   <div class="vs-finance-grid">
   <?php foreach($ledger['payments'] as $payment):$void=$payment['storniert_am']!==null;?>
     <article class="vs-card vs-finance-entry"><div class="vs-finance-entry__head"><h3><?= V::escape(Money::format((int)$payment['betrag_cent'])) ?></h3><span class="vs-chip vs-chip--outline"><?= $void?'Storniert':'Verbucht' ?></span></div>
       <p class="vs-muted"><?= V::escape(date('d.m.Y',strtotime($payment['gezahlt_am']))) ?> · <?= V::escape($payment['zahlungsweg']) ?> · #<?= (int)$payment['id'] ?></p>
       <?php if($payment['referenz']!==''):?><p>Referenz: <?= V::escape($payment['referenz']) ?></p><?php endif; ?>
       <p>Zuordnung: <?= V::escape(Money::format($void?0:(int)$payment['zugeordnet_cent'])) ?></p>
       <?php if($canVoid&&!$void):?><form method="post" class="vs-finance-inline" action="<?= V::escape($voidPaymentUrl) ?>"><input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$payment['id'] ?>"><input type="hidden" name="member" value="<?= (int)$memberId ?>"><input type="hidden" name="year" value="<?= (int)$year ?>"><label class="vs-field"><span class="vs-label">Stornogrund</span><input class="vs-input" name="reason" required minlength="5" maxlength="250"></label><button type="submit" class="vs-button">Zahlung stornieren</button></form><?php endif; ?>
     </article>
   <?php endforeach;?></div>
  </section>
  <?php if($canManage):?>
  <section class="vs-section"><h2>Buchungen erfassen</h2><div class="vs-finance-grid">
    <div class="vs-card"><h3>Zahlungseingang</h3><form class="vs-form" action="<?= V::escape($paymentUrl) ?>" method="post">
      <input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>"><input type="hidden" name="member" value="<?= (int)$memberId ?>"><input type="hidden" name="year" value="<?= (int)$year ?>">
      <label class="vs-field"><span class="vs-label">Betrag (€)</span><input class="vs-input" name="amount" inputmode="decimal" placeholder="15,00" required></label>
      <?php if($cashAccounts):?><label class="vs-field"><span class="vs-label">Kassenkonto für den Eingang</span><select class="vs-select" name="account" required><option value="">Konto wählen</option><?php foreach($cashAccounts as $cashAccount):?><option value="<?= (int)$cashAccount->id ?>"><?= V::escape($cashAccount->name) ?></option><?php endforeach;?></select></label>
      <?php else:?><p class="vs-muted">Noch kein Kassenkonto eingerichtet. Zuerst unter „Kasse“ ein Konto anlegen.</p><?php endif; ?>
      <label class="vs-field"><span class="vs-label">Zahlungsdatum</span><input class="vs-input" name="date" type="date" required value="<?= V::escape(date('Y-m-d')) ?>"></label>
      <label class="vs-field"><span class="vs-label">Zahlungsweg</span><select class="vs-select" name="channel"><?php foreach(['Überweisung','Bar','Lastschrift','PayPal','Sonstiges'] as $option):?><option><?= V::escape($option) ?></option><?php endforeach;?></select></label>
      <label class="vs-field"><span class="vs-label">Bevorzugte Forderungs-ID (optional)</span><select class="vs-select" name="claim"><option value="">Älteste offene zuerst</option><?php foreach($ledger['claims'] as $claim):if($claim['storniert_am']!==null||(int)$claim['betrag_cent']<=(int)$claim['bezahlt_cent'])continue;?><option value="<?= (int)$claim['id'] ?>">#<?= (int)$claim['id'] ?> · <?= V::escape($claim['beschreibung']) ?></option><?php endforeach;?></select></label>
      <label class="vs-field"><span class="vs-label">Referenz (optional)</span><input class="vs-input" name="reference" maxlength="190"></label>
      <label class="vs-field"><span class="vs-label">Bemerkung (optional)</span><textarea class="vs-textarea" name="note" maxlength="500" rows="2"></textarea></label>
      <button class="vs-button vs-button--primary" type="submit">Zahlung buchen</button></form></div>
    <div class="vs-card"><h3>Einzelforderung</h3><form class="vs-form" action="<?= V::escape($claimUrl) ?>" method="post">
      <input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>"><input type="hidden" name="member" value="<?= (int)$memberId ?>"><input type="hidden" name="year" value="<?= (int)$year ?>">
      <label class="vs-field"><span class="vs-label">Beitragsmonat</span><input class="vs-input" name="period" type="month" required value="<?= V::escape(date('Y-m')) ?>"></label>
      <label class="vs-field"><span class="vs-label">Art</span><select class="vs-select" name="kind"><option value="sonstiges">Sonstiges</option><option value="grundbeitrag">Grundbeitrag</option><option value="abteilung">Abteilung</option></select></label>
      <label class="vs-field"><span class="vs-label">Abteilungs-ID (nur bei Abteilungsbeitrag)</span><input class="vs-input" name="department" type="number" min="0" value="0"></label>
      <label class="vs-field"><span class="vs-label">Beschreibung</span><input class="vs-input" name="description" required minlength="3" maxlength="190"></label>
      <label class="vs-field"><span class="vs-label">Betrag (€)</span><input class="vs-input" name="amount" required inputmode="decimal" placeholder="15,00"></label>
      <label class="vs-field"><span class="vs-label">Fällig am</span><input class="vs-input" name="due" type="date" required value="<?= V::escape(date('Y-m-d')) ?>"></label>
      <button class="vs-button vs-button--primary" type="submit">Forderung anlegen</button></form></div>
  </div>
  <form class="vs-finance-inline vs-card" action="<?= V::escape($reconcileUrl) ?>" method="post"><input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>"><input type="hidden" name="member" value="<?= (int)$memberId ?>"><input type="hidden" name="year" value="<?= (int)$year ?>"><p>Vorhandenes Guthaben nach Fälligkeit auf offene Forderungen verteilen.</p><button class="vs-button" type="submit">Guthaben zuordnen</button></form>
  </section>
  <?php endif;?>
  <?php if($canGenerate):?>
    <section class="vs-section vs-card"><h2>Monatliche Sollstellung</h2><p class="vs-muted">Berücksichtigt aktive Mitglieder, Alters-Tarife und aktive Abteilungszugehörigkeiten. Bereits erzeugte Forderungen werden nicht doppelt gebucht.</p>
      <form class="vs-finance-inline" method="post" action="<?= V::escape($generateUrl) ?>"><input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>"><input type="hidden" name="year" value="<?= (int)$year ?>"><label class="vs-field"><span class="vs-label">Beitragsmonat</span><input class="vs-input" type="month" required name="period" value="<?= V::escape(date('Y-m')) ?>"></label><button class="vs-button vs-button--primary" type="submit">Sollstellung erzeugen</button></form>
    </section>
  <?php endif;?>
</section>
