<?php
/** An immutable connection between an event task and an active club member. */
declare(strict_types=1);
namespace Vereinssystem\Models;

final readonly class TaskAssignment
{
    public function __construct(public int $memberId)
    {
        if ($memberId < 1) throw new \InvalidArgumentException('Ungültige Mitglieds-ID.');
    }
}
