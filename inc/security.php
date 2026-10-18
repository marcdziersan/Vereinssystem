<?php
/**
 * Vereinssystem 2.3.1 – Sichere Ausgabe historischer HTML-Nachrichten.
 *
 * Alte Nachrichten können eingebettete HTML-Attribute enthalten. Die erlaubten
 * Textelemente werden neu aufgebaut; JavaScript, Styles und Event-Attribute
 * werden nie aus der Eingabe übernommen. Reine Textausgabe ist der Fallback.
 *
 * @license PolyForm-Noncommercial-1.0.0 (eigene Ergänzungen)
 */
declare(strict_types=1);

/** Renderfähige, aber streng eingeschränkte HTML-Fragmente. */
function vs_safe_html(string $raw): string
{
    if (!class_exists(\DOMDocument::class)) {
        // Ohne ext-dom darf insbesondere strip_tags() nicht zur HTML-Säuberung
        // verwendet werden: Es lässt bösartige HTML-Attribute unangetastet.
        return nl2br(htmlspecialchars(strip_tags($raw), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    $doc = new \DOMDocument('1.0', 'UTF-8');
    $oldErrors = libxml_use_internal_errors(true);
    try {
        if (!$doc->loadHTML('<?xml encoding="UTF-8"?><div id="vs-fragment">'. $raw .'</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return htmlspecialchars(strip_tags($raw), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $nodes = $doc->getElementsByTagName('div');
        $root = $nodes->item(0);
        if (!$root) {
            return htmlspecialchars(strip_tags($raw), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $output = '';
        foreach ($root->childNodes as $node) {
            $output .= vs_safe_html_node($node);
        }
        return $output;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($oldErrors);
    }
}

/** Rekursiver Allowlist-Renderer – keine fremden Attribute übernehmen. */
function vs_safe_html_node(\DOMNode $node): string
{
    if ($node instanceof \DOMText) {
        return htmlspecialchars($node->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    if (!$node instanceof \DOMElement) {
        return '';
    }
    $tag = strtolower($node->tagName);
    $allowed = ['p','br','strong','b','em','i','u','ul','ol','li','blockquote','a','img'];
    if (!in_array($tag, $allowed, true)) {
        // Script-/Style-/Embed-Inhalte komplett verwerfen, nicht nur das Tag.
        if (in_array($tag, ['script','style','iframe','object','svg','math','form','template'], true)) {
            return '';
        }
        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= vs_safe_html_node($child);
        }
        return $text;
    }
    if ($tag === 'br') {
        return '<br>';
    }
    $attrs = '';
    if ($tag === 'a') {
        $href = trim($node->getAttribute('href'));
        if ($href !== '' && vs_safe_link($href)) {
            $attrs = ' href="'.htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" rel="noopener noreferrer"';
        }
    }
    if ($tag === 'img') {
        $src = trim($node->getAttribute('src'));
        if (!vs_safe_link($src) || $src === '' || preg_match('~^(https?://|mailto:)~i', $src)) {
            return '';
        }
        return '<img src="'.htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" alt="'.
            htmlspecialchars(mb_substr_safe($node->getAttribute('alt'), 0, 120), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';
    }
    $content = '';
    foreach ($node->childNodes as $child) {
        $content .= vs_safe_html_node($child);
    }
    return '<'.$tag.$attrs.'>'.$content.'</'.$tag.'>';
}

function mb_substr_safe(string $s, int $offset, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($s, $offset, $length, 'UTF-8') : substr($s, $offset, $length);
}

/** Nur absolute HTTPS/HTTP-URLs, Mailto oder anwendungsinterne Pfade. */
function vs_safe_link(string $href): bool
{
    if (preg_match('/[\x00-\x20\x7F]/', $href)) {
        return false;
    }
    if (preg_match('~^https?://[^/\s]+(?:/[^\s]*)?$~iD', $href)) {
        return true;
    }
    if (preg_match('~^mailto:[^@\s]+@[^@\s]+$~iD', $href)) {
        return true;
    }
    return (bool) preg_match('~^(?!//)[a-zA-Z0-9_./-]+(?:\?[a-zA-Z0-9_.%=&+-]*)?$~D', $href)
        && !str_contains($href, '..');
}

/** Für Kopfzeilen müssen CR/LF und Steuerzeichen entfernt werden. */
function vs_mail_header_value(string $value): string
{
    return trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value) ?? '');
}
