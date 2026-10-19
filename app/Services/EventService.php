<?php
/** Event validation and business actions; controllers never compose SQL. */
declare(strict_types=1);
namespace Vereinssystem\Services;

use DateTimeImmutable;
use DateTimeZone;
use Vereinssystem\Events\{EventRepository,EventValidationException};
use Vereinssystem\Models\Event;

final class EventService
{
    public function __construct(private readonly EventRepository $events) {}

    /** Strict local-to-Unix conversion; nonexistent daylight-saving times are invalid. */
    public static function toTimestamp(string $value): ?int
    {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('Europe/Berlin'));
        return $dt !== false && $dt->format('Y-m-d\TH:i') === $value ? $dt->getTimestamp() : null;
    }

    /** @param array<string,string> $fields */
    public function validate(array $fields, ?int $id = null): Event
    {
        $name = trim($fields['name'] ?? '');
        $description = trim($fields['description'] ?? '');
        $start = self::toTimestamp($fields['start'] ?? '');
        $end = self::toTimestamp($fields['end'] ?? '');
        $place = filter_var($fields['place'] ?? '0', FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
        $contact = filter_var($fields['contact'] ?? '0', FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
        $errors = [];
        if ($name === '' || self::length($name) > 250) $errors['name'] = 'Bitte einen Namen mit höchstens 250 Zeichen angeben.';
        if (self::length($description) > 10000) $errors['description'] = 'Beschreibung zu lang.';
        if ($start === null || $end === null || $end <= $start || $end > 2147483647 || $end - $start > 366 * 86400) {
            $errors['start'] = 'Beginn und Ende müssen gültig sein (maximal 366 Tage).';
        }
        if ($place === false) $errors['place'] = 'Veranstaltungsort ungültig.';
        if ($contact === false) $errors['contact'] = 'Ansprechpartner ungültig.';
        if ($errors !== []) throw new EventValidationException($errors);
        return new Event($id, $name, $description, $start, $end, (int)$place, (int)$contact);
    }

    /** @param array<string,string> $fields */
    public function save(array $fields, ?int $id): int
    {
        if ($id !== null && ($id < 1 || $this->events->find($id) === null)) {
            throw new \DomainException('Veranstaltung nicht gefunden.');
        }
        return $this->events->save($this->validate($fields, $id));
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
