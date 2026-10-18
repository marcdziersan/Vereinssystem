/**
 * Vereinssystem 2.4.0 – progressive UX für alle PHP-Seiten, ohne Buildprozess.
 * Keine Formulardaten verlassen den Browser. Bestehende Formular-POSTs und URLs
 * bleiben unverändert. Falls JavaScript fehlt, funktionieren Standardseiten weiter.
 */
(() => {
  'use strict';
  const doc = document;
  const root = doc.documentElement;
  const body = doc.body;
  const menu = doc.querySelector('#navi.vs-sidebar');
  const triggers = [...doc.querySelectorAll('[data-vs-menu]')];
  const trigger = triggers[0] || null;
  const scrim = doc.querySelector('.vs-nav-scrim');
  let lastFocus = null;

  /** Mobile Navigation mit Escape, Fokusführung und Scroll-Sperre. */
  const closeMenu = () => {
    if (!menu || !trigger) return;
    menu.dataset.open = 'false';
    triggers.forEach(control => control.setAttribute('aria-expanded', 'false'));
    trigger.setAttribute('aria-label', 'Menü öffnen');
    if (scrim) scrim.hidden = true;
    body.classList.remove('vs-drawer-open');
    if (lastFocus instanceof HTMLElement) lastFocus.focus();
    lastFocus = null;
  };
  const openMenu = () => {
    if (!menu || !trigger) return;
    lastFocus = doc.activeElement;
    menu.dataset.open = 'true';
    triggers.forEach(control => control.setAttribute('aria-expanded', 'true'));
    trigger.setAttribute('aria-label', 'Menü schließen');
    if (scrim) scrim.hidden = false;
    body.classList.add('vs-drawer-open');
    menu.querySelector('a')?.focus();
  };
  triggers.forEach(control => control.addEventListener('click', () => menu?.dataset.open === 'true' ? closeMenu() : openMenu()));
  doc.querySelectorAll('[data-vs-close]').forEach((item) => item.addEventListener('click', closeMenu));
  doc.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menu?.dataset.open === 'true') closeMenu();
    if (event.key !== 'Tab' || menu?.dataset.open !== 'true' || matchMedia('(min-width:1100px)').matches) return;
    const focusables = [...menu.querySelectorAll('a[href],button:not([disabled])')];
    if (!focusables.length) return;
    const first = focusables[0], last = focusables.at(-1);
    if (event.shiftKey && doc.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && doc.activeElement === last) { event.preventDefault(); first.focus(); }
  });
  matchMedia('(min-width:1100px)').addEventListener?.('change', closeMenu);

  /** Optionales Farbschema, lokal gespeichert; kein Benutzertracking. */
  let savedTheme = null;
  try { savedTheme = localStorage.getItem('vs-color-theme'); } catch (_) {}
  if (savedTheme === 'light' || savedTheme === 'dark') root.dataset.theme = savedTheme;
  doc.querySelectorAll('[data-vs-theme]').forEach(button => {
    button.addEventListener('click', () => {
      const dark = root.dataset.theme === 'dark' || (!root.dataset.theme && matchMedia('(prefers-color-scheme:dark)').matches);
      root.dataset.theme = dark ? 'light' : 'dark';
      try { localStorage.setItem('vs-color-theme', root.dataset.theme); } catch (_) {}
    });
  });

  /** Label-gesteuerte Mobilkarten für echte einfache Datentabellen.
   * Komplexe Tabellen (colspan, gruppierte Kopfzeilen, Formulare) behalten Scrollen.
   */
  doc.querySelectorAll('#content table').forEach(table => {
    let wrapper = table.parentElement;
    if (!wrapper || wrapper.tagName !== 'DIV' || !wrapper.matches('.scroll,.vs-table-scroll')) {
      wrapper = doc.createElement('div');
      wrapper.className = 'vs-table-scroll';
      table.parentNode?.insertBefore(wrapper,table);
      wrapper.append(table);
    }
    wrapper.classList.add('vs-table-scroll');
    wrapper.tabIndex = 0;
    wrapper.setAttribute('role', 'region');
    wrapper.setAttribute('aria-label', 'Tabelle – bei Bedarf horizontal scrollen');
    const rows = [...table.querySelectorAll(':scope > tbody > tr')];
    const heads = [...table.querySelectorAll(':scope > thead > tr:last-child > th')];
    const regular = heads.length >= 2 && heads.length <= 7 &&
      rows.every(row => row.cells.length === heads.length && [...row.cells].every(cell => cell.colSpan === 1 && cell.rowSpan === 1)) &&
      rows.length <= 200 && !table.querySelector('thead [colspan],tbody [colspan],tbody [rowspan], tbody form,tbody input,tbody select');
    if (regular) {
      rows.forEach(row => [...row.cells].forEach((cell,index) => {
        const label = heads[index].textContent.trim().replace(/\s+/g,' ').slice(0,70);
        cell.dataset.vsLabel = label || `Spalte ${index+1}`;
      }));
      wrapper.classList.add('vs-card-table');
    }
  });

  /** Password-Toggle für Login; verändert weder Name noch Passwortwert. */
  doc.querySelectorAll('.vs-password-toggle').forEach(button => {
    const input = doc.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    button.addEventListener('click', () => {
      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      button.setAttribute('aria-pressed',String(!visible));
      button.setAttribute('aria-label', visible ? 'Passwort anzeigen' : 'Passwort verbergen');
      input.focus();
    });
  });

  /** Formulare bleiben serverseitig validiert; Client-Hilfe für bekannte Felder. */
  doc.querySelectorAll('input[type="search"]').forEach(input => {
    if (!input.hasAttribute('autocomplete')) input.autocomplete = 'off';
  });
  doc.querySelectorAll('#content form').forEach(form => {
    // Keine automatische Sperre für Doppel-Submits: zahlreiche Altmasken haben
    // mehrere Submit-Aktionen; die Server-Nonces entscheiden verbindlich.
    form.querySelectorAll('button[type="submit"],input[type="submit"]').forEach(button => {
      button.addEventListener('click', () => form.dataset.vsTouched = 'true');
    });
  });
})();
