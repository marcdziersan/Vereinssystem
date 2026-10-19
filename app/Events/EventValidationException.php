<?php
/** Validation messages remain field-specific, never leak SQL or internal errors. */
declare(strict_types=1);
namespace Vereinssystem\Events;

final class EventValidationException extends \DomainException
{
    /** @param array<string,string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Bitte die markierten Veranstaltungsfelder prüfen.');
    }
}
