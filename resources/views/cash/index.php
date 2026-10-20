<?php
/** Mobile-first cashbook. Inputs are POST+CSRF; all displayed external data is escaped. */
declare(strict_types=1);
use Vereinssystem\Core\View as V;
use Vereinssystem\Finance\Money;
$activeAccounts=array_values(array_filter($accounts,static fn($a)=>$a->active));
$total=array_sum(array_map(static fn($a)=>$a->balanceCents,$accounts));
?>
<section class="vs-cash" aria-labelledby="cash-title">
  <div class="vs-page-head"><div><p class="vs-kicker">Vereinsfinanzen · Buchungsjournal</p><h1 id="cash-title">Kassenverwaltung</h1>
    <p class="vs-muted">Frei definierbare Konten, centgenaue Buchungen und nachvollziehbare Historie.</p></div></div>
  <?php if($flash!==''):?><div class="vs-alert" role="status"><?= V::escape($flash) ?></div><?php endif;?>
  <div class="vs-card vs-cash-total"><span>Gesamtbestand über alle Konten</span><strong><?= V::escape(Money::format($total)) ?></strong><small class="vs-muted">Umbuchungen verändern den Gesamtbestand nicht.</small></div>
  <div class="vs-form-actions"><a class="vs-button" href="<?= V::escape($reportUrl.'&year='.$year) ?>">Jahresbericht / CSV / PDF</a></div>
  <section class="vs-section" aria-labelledby="cash-accounts"><h2 id="cash-accounts">Konten</h2>
    <div class="vs-cash-grid">
      <?php foreach($accounts as $a):?>
        <article class="vs-card vs-cash-account"><div class="vs-finance-entry__head"><h3><?= V::escape($a->name) ?></h3><span class="vs-chip vs-chip--outline"><?= $a->active?'Aktiv':'Inaktiv' ?></span></div>
          <p class="vs-muted"><?= V::escape($a->type) ?> · Eröffnung <?= V::escape($a->openingDate) ?></p>
          <strong><?= V::escape(Money::format($a->balanceCents)) ?></strong>
          <?php if($canAccounts):?>
            <details><summary>Konto verwalten</summary><form method="post" action="<?= V::escape($updateAccountUrl) ?>" class="vs-form">
              <input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>"><input type="hidden" name="id" value="<?= $a->id ?>">
              <label class="vs-field"><span class="vs-label">Kontoname</span><input class="vs-input" name="name" required maxlength="120" value="<?= V::escape($a->name) ?>"></label>
              <label class="vs-field"><span class="vs-label">Kontoart</span><input class="vs-input" name="type" maxlength="40" required value="<?= V::escape($a->type) ?>"></label>
              <label class="vs-field"><span class="vs-label">Status</span><select class="vs-select" name="active"><option value="1" <?= $a->active?'selected':'' ?>>Aktiv</option><option value="0" <?= !$a->active?'selected':'' ?>>Inaktiv</option></select></label>
              <button class="vs-button" type="submit">Konto speichern</button>
            </form></details>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
      <?php if(!$accounts):?><div class="vs-card">Noch keine Konten eingerichtet. Beginne mit dem Anlegen eines Kassenkontos.</div><?php endif; ?>
    </div>
  </section>
  <?php if($canAccounts):?><section class="vs-section vs-card"><h2>Konto anlegen</h2>
    <form class="vs-field-grid" action="<?= V::escape($accountUrl) ?>" method="post">
      <input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>">
      <label class="vs-field"><span class="vs-label">Name</span><input class="vs-input" name="name" required maxlength="120" placeholder="Vereinskasse"></label>
      <label class="vs-field"><span class="vs-label">Typ</span><input class="vs-input" name="type" required maxlength="40" placeholder="Bargeld, Bank, PayPal …"></label>
      <label class="vs-field"><span class="vs-label">Anfangsbestand (€)</span><input class="vs-input" name="opening" required value="0,00" inputmode="decimal"></label>
      <label class="vs-field"><span class="vs-label">Eröffnungsdatum</span><input class="vs-input" name="date" required type="date" value="<?= V::escape(date('Y-m-d')) ?>"></label>
      <button class="vs-button vs-button--primary" type="submit">Konto erstellen</button>
    </form></section><?php endif; ?>
  <?php if($canManage && $activeAccounts):?>
    <section class="vs-section" aria-labelledby="cash-actions"><h2 id="cash-actions">Buchungen</h2><div class="vs-cash-grid">
      <div class="vs-card"><h3>Einnahme / Ausgabe</h3><form class="vs-form" method="post" action="<?= V::escape($bookUrl) ?>">
        <input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>">
        <label class="vs-field"><span class="vs-label">Konto</span><select class="vs-select" name="account" required><?php foreach($activeAccounts as $a):?><option value="<?= $a->id ?>"><?= V::escape($a->name) ?></option><?php endforeach;?></select></label>
        <label class="vs-field"><span class="vs-label">Buchungsart</span><select class="vs-select" name="type"><option value="einnahme">Einnahme</option><option value="ausgabe">Ausgabe</option></select></label>
        <label class="vs-field"><span class="vs-label">Betrag (€)</span><input class="vs-input" name="amount" inputmode="decimal" required placeholder="25,00"></label>
        <label class="vs-field"><span class="vs-label">Datum</span><input class="vs-input" type="date" name="date" required value="<?= V::escape(date('Y-m-d')) ?>"></label>
        <label class="vs-field"><span class="vs-label">Kategorie</span><input class="vs-input" name="category" maxlength="100" placeholder="Spende, Material, Miete …"></label>
        <label class="vs-field"><span class="vs-label">Beschreibung</span><textarea class="vs-textarea" name="description" required maxlength="500"></textarea></label>
        <label class="vs-field"><span class="vs-label">Belegnummer (optional)</span><input class="vs-input" name="receipt" maxlength="100"></label>
        <button class="vs-button vs-button--primary" type="submit">Buchung erfassen</button>
      </form></div>
      <div class="vs-card"><h3>Zwischen Konten umbuchen</h3><form class="vs-form" method="post" action="<?= V::escape($transferUrl) ?>">
        <input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>">
        <label class="vs-field"><span class="vs-label">Von</span><select class="vs-select" name="from" required><?php foreach($activeAccounts as $a):?><option value="<?= $a->id ?>"><?= V::escape($a->name) ?></option><?php endforeach;?></select></label>
        <label class="vs-field"><span class="vs-label">Nach</span><select class="vs-select" name="to" required><?php foreach($activeAccounts as $a):?><option value="<?= $a->id ?>"><?= V::escape($a->name) ?></option><?php endforeach;?></select></label>
        <label class="vs-field"><span class="vs-label">Betrag (€)</span><input class="vs-input" name="amount" required inputmode="decimal" placeholder="50,00"></label>
        <label class="vs-field"><span class="vs-label">Datum</span><input class="vs-input" name="date" type="date" required value="<?= V::escape(date('Y-m-d')) ?>"></label>
        <label class="vs-field"><span class="vs-label">Grund</span><textarea class="vs-textarea" name="description" required maxlength="500"></textarea></label>
        <button class="vs-button vs-button--primary" type="submit">Umbuchen</button>
      </form></div>
    </div></section>
  <?php endif;?>
  <section class="vs-section" aria-labelledby="cash-journal"><h2 id="cash-journal">Kassenjournal</h2>
    <form class="vs-searchbar" action="<?= V::escape($baseUrl) ?>" method="get"><input type="hidden" name="r" value="/cash">
      <label class="vs-field"><span class="vs-label">Jahr</span><input class="vs-input" name="year" type="number" min="2000" max="2099" value="<?= $year ?>"></label>
      <label class="vs-field"><span class="vs-label">Konto</span><select class="vs-select" name="account"><option value="">Alle Konten</option><?php foreach($accounts as $a):?><option value="<?= $a->id ?>" <?= $accountFilter===$a->id?'selected':'' ?>><?= V::escape($a->name) ?></option><?php endforeach;?></select></label>
      <button class="vs-button" type="submit">Filtern</button>
    </form>
    <p class="vs-muted">Bis zu 300 neueste Buchungen im ausgewählten Jahr. Stornos bleiben sichtbar.</p>
    <div class="vs-cash-grid">
    <?php foreach($entries as $entry):
      $isPlus=in_array($entry->type,['einnahme','umbuchung_ein'],true);
    ?><article class="vs-card vs-cash-entry">
      <div class="vs-finance-entry__head"><h3><?= V::escape($entry->description) ?></h3><span class="vs-chip vs-chip--outline"><?= $entry->void?'Storniert':V::escape(str_replace('_',' ',$entry->type)) ?></span></div>
      <p class="vs-muted">#<?= $entry->id ?> · <?= V::escape($entry->date) ?> · <?= V::escape($entry->accountName) ?></p>
      <strong class="<?= $isPlus?'vs-cash-plus':'vs-cash-minus' ?>"><?= $isPlus?'+':'−' ?><?= V::escape(Money::format($entry->amountCents)) ?></strong>
      <?php if($entry->category!==''):?><p class="vs-muted"><?= V::escape($entry->category) ?></p><?php endif;?>
      <?php if($entry->receiptNumber!==''):?><p class="vs-muted">Belegnr.: <?= V::escape($entry->receiptNumber) ?></p><?php endif;?>
      <?php foreach(($receipts[$entry->id]??[]) as $file):?>
        <p class="vs-muted"><a href="<?= V::escape($receiptDownloadUrl.'&id='.$file->id) ?>">Beleg: <?= V::escape($file->originalName) ?></a>
        (<?= (int)ceil($file->size/1024) ?> KB)</p>
      <?php endforeach; ?>
      <?php if($canReceipts):?><details><summary>Beleg hochladen</summary>
        <form method="post" action="<?= V::escape($receiptUploadUrl) ?>" enctype="multipart/form-data" class="vs-form">
          <input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>">
          <input type="hidden" name="entry" value="<?= $entry->id ?>">
          <label class="vs-field"><span class="vs-label">PDF oder Bild (max. 8 MB)</span>
            <input class="vs-input" type="file" name="receipt" accept="application/pdf,image/png,image/jpeg,image/webp" required></label>
          <button class="vs-button" type="submit">Beleg sicher speichern</button>
        </form></details><?php endif; ?>
      <?php if($entry->paymentId!==null):?><p class="vs-muted">Beitragszahlung #<?= $entry->paymentId ?> · Storno nur über Beitragskonto</p><?php endif;?>
      <?php if($canVoid&&!$entry->void&&$entry->paymentId===null):?><details><summary>Stornieren</summary>
        <form class="vs-form" method="post" action="<?= V::escape($voidUrl) ?>"><input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>"><input type="hidden" name="id" value="<?= $entry->id ?>">
          <label class="vs-field"><span class="vs-label">Stornogrund</span><input class="vs-input" name="reason" required minlength="5" maxlength="250"></label>
          <button class="vs-button" type="submit">Buchung stornieren</button></form></details><?php endif; ?>
    </article><?php endforeach;?>
    <?php if(!$entries):?><div class="vs-card">Keine Buchungen für diesen Zeitraum.</div><?php endif; ?>
    </div>
  </section>
</section>
