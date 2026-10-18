/**
 * Vereinssystem UI Kit: dependency-free interactive foundations.
 * This module is deliberately small; the kernel has no jQuery Mobile dependency.
 * jQuery 3.6.1 is available locally for future domain modules, if required.
 * Every enhancement is optional; content remains usable when JS is disabled.
 */
(() => {
    'use strict';

    /** Save a user-selected color scheme. Storage failures must not break UI. */
    function saveTheme(value) {
        try {
            window.localStorage.setItem('vs-color-theme', value);
        } catch (_error) {
            // Privacy mode/disabled storage: retain the in-memory document theme.
        }
    }

    /** Read the saved preference without depending on storage availability. */
    function readTheme() {
        try {
            const value = window.localStorage.getItem('vs-color-theme');
            return value === 'light' || value === 'dark' ? value : null;
        } catch (_error) {
            return null;
        }
    }

    /** Apply an explicit theme; absence means the system color preference wins. */
    function applyTheme(value) {
        if (value === null) {
            document.documentElement.removeAttribute('data-theme');
        } else {
            document.documentElement.setAttribute('data-theme', value);
        }
    }

    /** Initialize progressive enhancement after DOM construction. */
    function init() {
        applyTheme(readTheme());
        const toggle = document.querySelector('[data-vs-theme-toggle]');
        if (!toggle) return;

        toggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const next = current === 'dark' || (current === null && prefersDark) ? 'light' : 'dark';
            applyTheme(next);
            saveTheme(next);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
