<?php
/** Mobile-first read-only annual overview; all data comes from the report model. */
declare(strict_types=1);
use Vereinssystem\Core\View as V;
use Vereinssystem\Finance\Money;
$query='&year='.$report->year.($report->accountId!==null?'&account='.$report->accountId:'');
?>
<section class="vs-reports" aria-labelledby="report-heading">
 <header class="vs-page-head"><div><p class="vs-kicker">Vereinskasse · Auswertung</p><h1 id="report-heading">Jahreskassenbericht <?= $report->year ?></h1>
 <p class="vs-muted">Alle Buchungen des Jahres, einschließlich der stornierten Datensätze. Salden berücksichtigen nur wirksame Buchungen.</p></div></header>
 <div class="vs-card">
  <form class="vs-form vs-report-filter" method="get" action="<?= V::escape(explode('?',$baseUrl)[0]) ?>">
   <input type="hidden" name="r" value="/cash/report">
   <label class="vs-field"><span class="vs-label">Jahr</span><input class="vs-input" type="number" name="year" min="2000" max="2099" value="<?= $report->year ?>" required></label>
   <label class="vs-field"><span class="vs-label">Konto</span><select class="vs-select" name="account"><option value="">Alle Konten</option>
    <?php foreach($report->accounts as $a):?><option value="<?= $a['id'] ?>" <?= $report->accountId===$a['id']?'selected':'' ?>><?= V::escape($a['name']) ?></option><?php endforeach; ?>
   </select></label>
   <button type="submit" class="vs-button">Auswertung öffnen</button>
  </form>
  <div class="vs-form-actions"><a class="vs-button" href="<?= V::escape($csvUrl.$query) ?>">CSV exportieren</a>
   <a class="vs-button" href="<?= V::escape($pdfUrl.$query) ?>">PDF-Kassenbericht</a>
   <a class="vs-button vs-button--secondary" href="<?= V::escape($cashUrl) ?>">Zur Kasse</a></div>
 </div>
 <div class="vs-cash-grid" aria-label="Gesamtergebnis">
  <?php foreach(['opening'=>'Bestand 01.01.','new_opening'=>'Neue Anfangsbestände','income'=>'Einnahmen','expense'=>'Ausgaben','closing'=>'Bestand 31.12.'] as $k=>$title):?>
  <article class="vs-card"><span class="vs-muted"><?= V::escape($title) ?></span><h2><?= V::escape(Money::format($report->totals[$k])) ?></h2></article><?php endforeach;?>
 </div>
 <section class="vs-section"><h2>Konten</h2><div class="vs-cash-grid">
 <?php foreach($report->accounts as $a):?><article class="vs-card"><h3><?= V::escape($a['name']) ?></h3>
 <p>01.01.: <?= V::escape(Money::format($a['opening'])) ?> · Eröffnung im Jahr: <?= V::escape(Money::format($a['new_opening'])) ?></p>
 <p>+ <?= V::escape(Money::format($a['income'])) ?> Einnahmen · − <?= V::escape(Money::format($a['expense'])) ?> Ausgaben</p>
 <p>Umbuchung: + <?= V::escape(Money::format($a['transfer_in'])) ?> / − <?= V::escape(Money::format($a['transfer_out'])) ?></p>
 <strong>31.12.: <?= V::escape(Money::format($a['closing'])) ?></strong>
 </article><?php endforeach; ?></div></section>
 <section class="vs-section"><h2>Alle Buchungen (<?= count($report->entries) ?>)</h2><div class="vs-cash-grid">
 <?php foreach($report->entries as $e):?><article class="vs-card"><div class="vs-finance-entry__head">
 <h3><?= V::escape($e['description']) ?></h3><span class="vs-chip vs-chip--outline"><?= $e['void']?'Storniert':V::escape($e['type']) ?></span></div>
 <p class="vs-muted"><?= V::escape($e['date']) ?> · <?= V::escape($e['account']) ?> · #<?= $e['id'] ?></p>
 <strong><?= V::escape(Money::format($e['amount'])) ?></strong>
 <?php if($e['category']!==''):?><p class="vs-muted"><?= V::escape($e['category']) ?></p><?php endif; ?>
 </article><?php endforeach;?>
 <?php if(!$report->entries):?><p class="vs-card">Keine Buchungen vorhanden.</p><?php endif; ?></div></section>
</section>
