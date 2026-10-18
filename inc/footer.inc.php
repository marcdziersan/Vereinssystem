<?php
/** Gemeinsamer Abschluss der Anwendung – Footer, Datenschutz/Urheberhinweis. */
require_once __DIR__.'/ui.php';
?>
<footer id="footer" class="vs-footer">
  <span><strong><?= vui_escape($settings['vereinsname_lang'] ?? $settings['vereinsname'] ?? 'Vereinssystem') ?></strong><span class="vs-footer-dot">·</span>Vereinssystem <?= vui_escape($settings['version'] ?? '2.4.0') ?></span>
  <span>Original: Nicolas Jourdan &amp; Sebastian Fuss (2013)<span class="vs-footer-dot">·</span>Weiterentwicklung seit 2026</span>
</footer>
</div>
</body>
</html>
