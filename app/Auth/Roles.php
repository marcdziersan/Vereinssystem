<?php
/**
 * Central access policy. Future modules must explicitly add capabilities here;
 * numeric rights are a compatibility input, NOT direct controller checks.
 */
declare(strict_types=1);

namespace Vereinssystem\Auth;

final class Roles
{
    public const NAMES = [
        0 => 'Gesperrt', 1 => 'Mitglied', 2 => 'Moderator',
        3 => 'Abteilungsleitung', 4 => 'Vorstand', 5 => 'Administration',
    ];

    /** @var array<string, int> Minimal level for currently implemented routes. */
    private const ACCESS = [
        'account.view' => 1,
        'rights.view' => 1,
        'rights.assign' => 5,
        'dashboard.view' => 1,
        'members.self' => 1,
        'members.list' => 4,
        'members.view' => 4,
        'members.edit' => 4,
        'members.create' => 5,
         'members.status' => 5,
        'places.list' => 4,
        'places.create' => 4,
        'places.edit' => 4,
        'places.status' => 5,
        'departments.list' => 1,
        'departments.view' => 1,
        'departments.create' => 4,
        'departments.edit' => 4,
        'departments.assign' => 4,
        'departments.status' => 5,
        // Veranstaltungen sind für Mitglieder lesbar; Mutationen nur mit separatem Recht.
        'events.list' => 1,
        'events.view' => 1,
        'events.create' => 4,
        'events.edit' => 4,
        'events.status' => 5,
        'events.tasks.manage' => 4,
    ];

    public function allows(User $user, string $permission): bool
    {
        $threshold = self::ACCESS[$permission] ?? null;
        return $user->active() && $threshold !== null && $user->rights >= $threshold;
    }

    public static function label(int $level): string
    {
        return self::NAMES[$level] ?? 'Unbekannt';
    }

    /** @return array<string, int> */
    public static function permissions(): array
    {
        return self::ACCESS;
    }
}
