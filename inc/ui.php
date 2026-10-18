<?php
/**
 * Vereinssystem UI 2.4.0: Präsentationshilfen, ohne Datenbankzugriffe.
 * Escaping und Icons liegen zentral, damit Navigation und Formulare konsistent bleiben.
 */

declare(strict_types=1);

if (!function_exists('vui_escape')) {
    function vui_escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** Gibt ausschließlich vorbereitete, vertrauenswürdige SVG-Pfade aus. */
function vui_icon(string $name, int $size = 20): string
{
    $shapes = [
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M5 5l14 14M19 5 5 19"/>',
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M9 21v-8h6v8"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'layers' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'pin' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/>',
        'news' => '<path d="M4 4h13v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/><path d="M8 8h6M8 12h6M8 16h4M17 8h4v10a2 2 0 0 1-2 2h-2"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="15" rx="2"/><path d="M3 10h18M5 6V4a2 2 0 0 1 2-2h12"/><path d="M16 15h2"/>',
        'chart' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 17v-5M12 17V7M17 17v-8"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19.4 15 1.3 1-2 3.4-1.6-.6a8 8 0 0 1-2 1.1l-.3 1.7h-4l-.3-1.7a8 8 0 0 1-2-1.1l-1.6.6-2-3.4 1.3-1a8 8 0 0 1 0-2l-1.3-1 2-3.4 1.6.6a8 8 0 0 1 2-1.1l.3-1.7h4l.3 1.7a8 8 0 0 1 2 1.1l1.6-.6 2 3.4-1.3 1a8 8 0 0 1 0 2Z"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>',
        'moon' => '<path d="M20.6 14a9 9 0 0 1-10.6-10.6A9 9 0 1 0 20.6 14Z"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'check' => '<path d="m5 12 5 5L20 7"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    ];
    $icon = $shapes[$name] ?? $shapes['layers'];
    $size = max(12, min(30, $size));
    return '<svg class="vs-icon" aria-hidden="true" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $icon . '</svg>';
}

/** Seitentitel und Navigationsgruppe hängen von einer festen Dateizuordnung ab. */
function vui_page(string $file): array
{
    $names = [
        'index.php'=>['Übersicht','start'],
        'login.php'=>['Anmelden','public'],
        'members.php'=>['Mitglieder','members'],
        'abteilungen.php'=>['Abteilungen','departments'],
        'orte.php'=>['Orte & Anschriften','locations'],
        'veranstaltungen.php'=>['Veranstaltungen','events'],
        'news.php'=>['Nachrichten','news'],
        'finanzen.php'=>['Beitragsverwaltung','finances'],
        'finanzkonto.php'=>['Beitragskonto','finances'],
        'member_finanzen.php'=>['Mitgliedsbeiträge','finances'],
        'kasse.php'=>['Kassenbuch','cash'],
        'kasse_admin.php'=>['Kassenadministration','cash'],
        'kasse_beleg.php'=>['Belege','cash'],
        'kasse_import.php'=>['CSV-Import','cash'],
        'kasse_protokoll.php'=>['Prüfprotokoll','cash'],
        'einstellungen.php'=>['Einstellungen','settings'],
        'rechtesystem.php'=>['Berechtigungen','settings'],
    ];
    if (isset($names[$file])) return $names[$file];
    foreach (['member_'=>'members','abteilung_'=>'departments','ort_'=>'locations','veranstaltung_'=>'events','dienste_'=>'events','news_'=>'news','kasse_'=>'cash'] as $prefix=>$group) {
        if (str_starts_with($file,$prefix)) {
            return [match(true) {
                str_contains($file,'_add') => 'Neu anlegen',
                str_contains($file,'_edit') => 'Bearbeiten',
                str_contains($file,'_delete') => 'Entfernen',
                default => 'Details',
            },$group];
        }
    }
    return ['Vereinsverwaltung','other'];
}

function vui_navlink(string $href, string $label, string $icon, string $group, string $active): string
{
    $selected = $group === $active;
    return '<li><a class="vs-nav-link' . ($selected ? ' is-active' : '') . '" href="' . vui_escape($href) . '"' . ($selected ? ' aria-current="page"' : '') . '>' . vui_icon($icon) . '<span>' . vui_escape($label) . '</span></a></li>';
}