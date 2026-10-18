<?php
/** Form validation carries only displayable field messages, no database details. */
declare(strict_types=1);
namespace Vereinssystem\Places;

final class PlaceValidationException extends \RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public readonly array $errors) {parent::__construct('Bitte Angaben prüfen.');}
}
