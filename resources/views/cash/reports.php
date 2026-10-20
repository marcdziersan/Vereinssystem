<?php
/** Accessible mobile-first annual accounts: numbers are escaped and formatted in cents. */
declare(strict_types=1);
use Vereinssystem\Core\View as V;
use Vereinssystem\Finance\Money;
$fmt=static fn(int $n):string=>Money::format($n);
$selected=$report->accountId;
$filter='&year='.$report->year.($selected!==null?'&account='.$selected:'');
?>
<section aria-labelledby="report-title" class="vs-section">
  <div class="vs-page-head"><div><p class="vs-kicker">Vereinskasse · Jahresabschluss</p><h1 id="report-title">Kassenbericht <?= $report->year ?></h1>
    <p class="vs-muted">Vollständiges Jahresjournal, einschließlich Stornos und Umbuchungen.</p></div></div>
  <p><a class="vs-button" href="<?= V::escape($backUrl) ?>">Zurück zur Kasse</a></p>
  <form class="vs-card vs-searchbar" method="get" action="<?= V::escape($reportUrl) ?>">
    <input type="hidden" name="r" value="/cash/reports">
    <label class="vs-field"><span class="vs-label">Berichtsjahr</span><input class="vs-input" type="number" name="year" min="2000" max="2099" value="<?= $report->year ?>"></label>
    <label class="vs-field"><span class="vs-label">Konto</span><select class="vs-select" name="account"><option value="">Alle Konten</option>
      <?php foreach($report->accounts as $a):?><option value="<?= $a['id'] ?>" <?= $selected===$a['id']?'selected':'' ?>><?= V::escape($a['name']) ?></option><?php endforeach; ?>
    </select></label><button class="vs-button vs-button--primary" type="submit">Auswerten</button>
  </form>
  <div class="vs-actions"><a class="vs-button" href="<?= V::escape($csvUrl.$filter) ?>">CSV herunterladen</a>
    <a class="vs-button" href="<?= V::escape($pdfUrl.$filter) ?>">PDF-Kassenbericht</a></div>
  <section class="vs-section"><h2>Jahresübersicht</h2><div class="vs-cash-grid">
    <?php foreach([
      'Anfangsbestand 01.01.'=>$report->totals['start'],
      'Kontoeröffnungen im Jahr'=>$report->totals['new_opening'],
      'Einnahmen'=>$report->totals['income'],
      'Ausgaben'=>$report->totals['expense'],
      'Umbuchungen Eingang'=>$report->totals['transfer_in'],
      'Umbuchungen Ausgang'=>$report->totals['transfer_out'],
      'Endbestand 31.12.'=>$report->totals['end']
    ] as $label=>$cent):?><article class="vs-card"><span class="vs-muted"><?= V::escape($label) ?></span><h3><?= V::escape($fmt($cent)) ?></h3></article><?php endforeach; ?>
  </div><p class="vs-muted"><?= $report->totals['rows'] ?> Buchungen einschließlich <?= $report->totals['void_count'] ?> Stornos; stornierte Beträge verändern die Salden nicht.</p></section>
  <section class="vs-section"><h2>Kontosalden</h2><div class="vs-cash-grid">
    <?php foreach($report->accounts as $a):?><article class="vs-card"><h3><?= V::escape($a['name']) ?></h3>
      <p class="vs-muted">Anfang: <?= V::escape($fmt($a['start'])) ?> · Eröffnungen: <?= V::escape($fmt($a['new_opening'])) ?></p>
      <strong><?= V::escape($fmt($a['end'])) ?></strong>
    </article><?php endforeach; ?>
  </div></section>
  <section class="vs-section"><h2>Auswertung nach Kategorien</h2><div class="vs-cash-grid">
    <?php foreach($report->categories as $cat):?><article class="vs-card"><h3><?= V::escape($cat['category']) ?></h3>
      <p>Einnahmen: <?= V::escape($fmt($cat['income'])) ?></p>
      <p>Ausgaben: <?= V::escape($fmt($cat['expense'])) ?></p></article><?php endforeach;?>
    <?php if(!$report->categories):?><p class="vs-muted">Keine Buchungen mit Kategorien.</p><?php endif;?>
  </div></section>
  <section class="vs-section"><h2>Monatsbewegungen</h2><div class="vs-cash-grid">
    <?php foreach($report->monthly as $m):?><article class="vs-card"><h3><?= sprintf('%02d',$m['month']) ?> / <?= $report->year ?></h3>
      <p>Einnahmen: <?= V::escape($fmt($m['income'])) ?> · Ausgaben: <?= V::escape($fmt($m['expense'])) ?></p>
      <p class="vs-muted">Umbuchungen: +<?= V::escape($fmt($m['transfer_in'])) ?> / −<?= V::escape($fmt($m['transfer_out'])) ?></p>
    </article><?php endforeach; ?>
  </div></section>
</section>
