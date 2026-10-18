<?php
/**
 * Kernel status landing page. No account, database or domain application data.
 * The purpose of this page is to verify Router → Controller → View → Response.
 * @var string $version
 * @var string $statusUrl
 */
declare(strict_types=1);

use Vereinssystem\Core\View;
?>
<section class="vs-hero" aria-labelledby="vs-title">
    <div class="vs-eyebrow"><span class="vs-status-dot" aria-hidden="true"></span> Entwicklungsmeilenstein 01</div>
    <h1 id="vs-title">Ein neuer Kern.<br><span class="vs-hero__accent">Ein sauberer Anfang.</span></h1>
    <p class="vs-hero__description">Der MVC-Kernel startet unabhängig vom alten PHP-System. Routing, Controller, Views, HTTP-Antworten und das mobile Designsystem sind eigenständig aufgebaut.</p>
    <div class="vs-actions">
        <a class="vs-button vs-button--primary" href="<?= View::escape($statusUrl) ?>">Kernel-Status als JSON ansehen <span aria-hidden="true">↗</span></a>
        <span class="vs-chip">Version <?= View::escape($version) ?></span>
    </div>
</section>

<section class="vs-section" aria-labelledby="components-title">
    <div class="vs-section__heading">
        <h2 id="components-title">Was dieser Meilenstein mitbringt</h2>
        <p>Bewusst nur Infrastruktur – jedes Fachmodul kommt später hinzu.</p>
    </div>
    <div class="vs-grid vs-grid--3">
        <article class="vs-card"><span class="vs-card__symbol" aria-hidden="true">{ }</span><h3>OOP & Routing</h3><p>Namensräume, Autoloader, feste HTTP-Routen und getrennte Controller.</p></article>
        <article class="vs-card"><span class="vs-card__symbol" aria-hidden="true">&lt;/&gt;</span><h3>Views & Ausgabe</h3><p>Serverseitige Templates, HTML-Escaping und kontrollierte HTTP-Antworten.</p></article>
        <article class="vs-card"><span class="vs-card__symbol" aria-hidden="true">◫</span><h3>Eigenes UI-Kit</h3><p>Responsive Layoutbausteine, Formulare, Buttons und Farbschemata ohne Framework.</p></article>
    </div>
</section>

<section class="vs-section vs-roadmap" aria-labelledby="next-title">
    <div>
        <p class="vs-eyebrow">Nächste Ausbaustufe</p>
        <h2 id="next-title">Fachmodule schrittweise ergänzen</h2>
        <p>Als Nächstes werden Authentifizierung und Berechtigungen vollständig neu implementiert. Danach folgen Dashboard und Mitgliederverwaltung. Alte PHP-Dateien und Legacy-Links sind kein Teil dieses Branches.</p>
    </div>
    <span class="vs-chip vs-chip--outline">Noch keine Fachfunktionen</span>
</section>
