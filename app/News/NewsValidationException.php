<?php
/** Field-specific, user-presentable validation failures. */
declare(strict_types=1);
namespace Vereinssystem\News;
final class NewsValidationException extends \DomainException
{
    /** @param array<string,string> $errors */
    public function __construct(public readonly array $errors)
    { parent::__construct('Nachricht enthält ungültige Angaben.'); }
}
