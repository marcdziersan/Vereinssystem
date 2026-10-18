<?php
/** One read-only member profile; admin-specific notes/actions are permission-filtered. */
declare(strict_types=1);
use Vereinssystem\Core\View;
$membershipNames=[0=>'Ordentliche Mitgliedschaft',1=>'Fördermitgliedschaft',2=>'Ruhende Mitgliedschaft'];
?>
<section class="vs-section vs-section--first">
    <a class="vs-back" href="<?= View::escape($backUrl) ?>">← Zurück</a>
    <?php if($flash!==''): ?><div role="status" class="vs-alert"><?= View::escape($flash) ?></div><?php endif; ?>
    <div class="vs-toolbar">
        <div><p class="vs-kicker">Mitgliedsprofil</p><h1><?= View::escape($member->fullName()) ?></h1><p class="vs-muted">Mitgliedsnummer <?= View::escape($member->number) ?></p></div>
        <?php if($canEdit): ?><a class="vs-button vs-button--primary" href="<?= View::escape($editUrl) ?>">Profil bearbeiten</a><?php endif; ?>
    </div>
    <div class="vs-grid vs-grid--2">
        <section class="vs-card" aria-labelledby="member-base"><h2 id="member-base">Mitgliedschaft</h2>
            <dl class="vs-details">
                <div><dt>Status</dt><dd><?= $member->status===1?'Aktiv':'Inaktiv' ?></dd></div>
                <div><dt>Art</dt><dd><?= View::escape($membershipNames[$member->membership]??'Unbekannt') ?></dd></div>
                <div><dt>Eintritt</dt><dd><?= View::escape($member->joinedAt) ?></dd></div>
                <div><dt>Geburtstag</dt><dd><?= View::escape($member->birthday) ?></dd></div>
            </dl>
        </section>
        <section class="vs-card" aria-labelledby="member-contact"><h2 id="member-contact">Kontakt</h2>
            <dl class="vs-details">
                <div><dt>E-Mail</dt><dd><?= $member->email!==''?View::escape($member->email):'Nicht hinterlegt' ?></dd></div>
                <div><dt>Mobil</dt><dd><?= $member->mobile!==''?View::escape($member->mobile):'Nicht hinterlegt' ?></dd></div>
            </dl>
        </section>
        <?php if($canSeeNotes): ?>
        <section class="vs-card" aria-labelledby="member-internal"><h2 id="member-internal">Verwaltungsdaten</h2>
            <dl class="vs-details">
                <div><dt>Benutzername</dt><dd><?= View::escape($member->username) ?></dd></div>
                <div><dt>Abrechnung</dt><dd><?= $member->billing===0?'Bankeinzug':'Überweisung' ?></dd></div>
                <div><dt>Anschrifts-ID</dt><dd><?= (int)$member->addressId ?></dd></div>
                <div><dt>Eltern-IDs</dt><dd><?= (int)$member->parent1 ?> / <?= (int)$member->parent2 ?></dd></div>
                <div><dt>Notizen</dt><dd class="vs-pre-line"><?= View::escape($member->notes) ?></dd></div>
            </dl>
        </section>
        <?php endif; ?>
    </div>
    <?php if($canStatus): ?>
    <section class="vs-card vs-status-card" aria-labelledby="status-heading">
        <h2 id="status-heading">Mitgliedsstatus</h2>
        <p class="vs-muted">Die Deaktivierung bewahrt den Datensatz. Ein inaktives Konto kann sich nicht anmelden.</p>
        <form method="post" action="<?= View::escape($statusUrl) ?>">
            <input type="hidden" name="_csrf" value="<?= View::escape($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= (int)$member->id ?>">
            <input type="hidden" name="status" value="<?= $member->status===1?2:1 ?>">
            <button class="vs-button" type="submit" data-vs-confirm="Mitgliedsstatus wirklich ändern?">
                <?= $member->status===1?'Mitglied deaktivieren':'Mitglied reaktivieren' ?>
            </button>
        </form>
    </section>
    <?php endif; ?>
</section>
