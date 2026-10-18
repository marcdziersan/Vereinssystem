<?php
/** Expected domain error: shows safe, already translated form messages. */
declare(strict_types=1);

namespace Vereinssystem\Members;

final class MemberValidationException extends \DomainException
{
    /** @param array<string,string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Mitgliedsdaten sind ungültig.');
    }
}
