<?php
/** Admin-only historical tariff list and create form. Existing rates cannot be overwritten. */
declare(strict_types=1);
use Vereinssystem\Core\View as V;
use Vereinssystem\Finance\Money;
?>
<section class="vs-section vs-section--first"><div class="vs-toolbar"><div><p class="vs-kicker">Beitragswesen</p><h1>Beitragssätze</h1><p class="vs-muted">Gültigkeitsdatum schützt vergangene Sollstellungen.</p></div><a class="vs-button" href="<?= V::escape($backUrl) ?>">Zurück</a></div>
<?php if($flash!==''):?><div class="vs-alert" role="status"><?= V::escape($flash) ?></div><?php endif;?>
<div class="vs-finance-grid"><section class="vs-card"><h2>Neuer Tarif</h2><form method="post" class="vs-form" action="<?= V::escape($saveUrl) ?>"><input type="hidden" name="_csrf" value="<?= V::escape($csrfToken) ?>">
<label class="vs-field"><span class="vs-label">Tarifart</span><select name="kind" class="vs-select"><option value="erwachsen">Erwachsene</option><option value="kind">Kinder</option><option value="abteilung">Abteilung</option></select></label>
<label class="vs-field"><span class="vs-label">Abteilungs-ID (nur bei Abteilung)</span><input class="vs-input" name="department" type="number" min="0" value="0"></label>
<label class="vs-field"><span class="vs-label">Gültig ab</span><input class="vs-input" name="effective" type="date" required value="<?= V::escape(date('Y-m-d')) ?>"></label>
<label class="vs-field"><span class="vs-label">Monatsbeitrag (€)</span><input class="vs-input" name="amount" inputmode="decimal" placeholder="15,00" required></label>
<button class="vs-button vs-button--primary" type="submit">Tarif speichern</button></form></section>
<section class="vs-card"><h2>Historische Tarifstände</h2><?php if(!$rates):?><p>Noch keine Tarife hinterlegt.</p><?php endif;?>
<div class="vs-finance-rates"><?php foreach($rates as $rate):?><div class="vs-finance-rate"><strong><?= V::escape($rate['art']) ?><?= (int)$rate['abteilung_id']>0?' · #'.(int)$rate['abteilung_id']:'' ?></strong>
<span><?= V::escape($rate['gueltig_ab']) ?> · <?= V::escape(Money::format((int)$rate['betrag_cent'])) ?></span></div><?php endforeach;?></div></section></div>
</section>
