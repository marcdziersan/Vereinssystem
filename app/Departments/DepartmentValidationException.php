<?php
/** Validated department form error details, safe to display as escaped text. */
declare(strict_types=1);
namespace Vereinssystem\Departments;
final class DepartmentValidationException extends \RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public readonly array $errors) {parent::__construct('Bitte Angaben prüfen.');}
}
