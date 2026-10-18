<?php
/**
 * Server-rendered PHP templates with explicit, isolated parameters.
 * Template names use a fixed directory, so user input cannot select a file.
 */
declare(strict_types=1);

namespace Vereinssystem\Core;

use RuntimeException;

final class View
{
    public function __construct(private readonly string $viewsDirectory) {}

    /** Escape untrusted values for HTML text and quoted attributes. */
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        if (!preg_match('~^[a-z0-9_-]+(?:/[a-z0-9_-]+)*$~iD', $template)) {
            throw new RuntimeException('Invalid template name.');
        }
        $file = $this->viewsDirectory . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Template does not exist.');
        }
        // Include inside a static closure prevents accidental scope leakage.
        return (static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                require $__file;
                return (string) ob_get_clean();
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        })($file, $data);
    }
}
