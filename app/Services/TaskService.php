<?php
/** Business rules for flexible event tasks and explicitly selected members. */
declare(strict_types=1);
namespace Vereinssystem\Services;

use Vereinssystem\Events\{EventRepository,EventValidationException};
use Vereinssystem\Models\{EventTask,TaskAssignment};

final class TaskService
{
    public function __construct(private readonly EventRepository $events) {}

    /**
     * Validate a freely defined task and the selected member IDs.
     * Multiple members are allowed, repeated IDs are normalized, unknown IDs are rejected by the repository.
     *
     * @param array<string,string> $fields
     * @param list<int|string> $members
     */
    public function validate(int $eventId, ?int $taskId, array $fields, array $members): EventTask
    {
        $title = trim($fields['title'] ?? '');
        $description = trim($fields['description'] ?? '');
        $place = trim($fields['place'] ?? '');
        $start = EventService::toTimestamp($fields['start'] ?? '');
        $end = EventService::toTimestamp($fields['end'] ?? '');
        $errors = [];
        if ($eventId < 1) $errors['title'] = 'Veranstaltung nicht gefunden.';
        if ($title === '' || self::length($title) > 160) $errors['title'] = 'Aufgabe fehlt oder ist zu lang (max. 160 Zeichen).';
        if ($place === '' || self::length($place) > 255) $errors['place'] = 'Ort fehlt oder ist zu lang (max. 255 Zeichen).';
        if (self::length($description) > 5000) $errors['description'] = 'Beschreibung zu lang.';
        if ($start === null || $end === null || $end <= $start || $end > 2147483647) {
            $errors['start'] = 'Bitte gültiges Datum, Beginn und späteres Ende angeben.';
        }
        if (count($members) > 500) $errors['members'] = 'Zu viele Mitglieder ausgewählt.';
        $ids = [];
        foreach ($members as $value) {
            if (!is_string($value) && !is_int($value)) { $errors['members'] = 'Ungültige Mitgliederauswahl.'; break; }
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if ($id === false) { $errors['members'] = 'Ungültige Mitgliederauswahl.'; break; }
            $ids[(int)$id] = new TaskAssignment((int)$id);
        }
        if ($errors !== []) throw new EventValidationException($errors);
        ksort($ids, SORT_NUMERIC);
        return new EventTask($taskId, $eventId, $title, $description, $place, $start, $end, array_values($ids));
    }

    /** @param array<string,string> $fields @param list<int|string> $members */
    public function save(int $eventId, ?int $taskId, array $fields, array $members, int $actorId): int
    {
        $event = $this->events->find($eventId);
        if (!$event || (int)$event['status'] !== 1) throw new \DomainException('Veranstaltung nicht aktiv.');
        if ($taskId !== null && ($taskId < 1 || $this->events->task($eventId, $taskId) === null)) {
            throw new \DomainException('Aufgabe nicht gefunden.');
        }
        return $this->events->saveTask($this->validate($eventId, $taskId, $fields, $members), $actorId);
    }

    public function archive(int $eventId, int $taskId): void
    {
        if ($eventId < 1 || $taskId < 1) throw new \DomainException('Ungültige Aufgabe.');
        $event = $this->events->find($eventId);
        if (!$event || (int)$event['status'] !== 1) throw new \DomainException('Veranstaltung nicht aktiv.');
        $this->events->archiveTask($eventId, $taskId);
    }

    private static function length(string $value): int
    { return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value); }
}
