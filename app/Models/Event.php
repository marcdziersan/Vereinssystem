<?php
/**
 * Domain model for a club event. Its values never depend on HTTP globals.
 * Persisted event dates remain Unix seconds for compatibility with the original table.
 */
declare(strict_types=1);
namespace Vereinssystem\Models;

final readonly class Event
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $description,
        public int $startsAt,
        public int $endsAt,
        public int $placeId,
        public int $contactId,
        public int $status = 1,
    ) {}

    /** Convert the domain model to the original database's column names. */
    public function values(): array
    {
        return [
            'veranstaltungsname' => $this->name,
            'beschreibung' => $this->description,
            'startzeit' => $this->startsAt,
            'endzeit' => $this->endsAt,
            'ort' => $this->placeId,
            'ansprechpartner' => $this->contactId,
        ];
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['veranstaltungs_id'], (string) $row['veranstaltungsname'],
            (string) $row['beschreibung'], (int) $row['startzeit'],
            (int) $row['endzeit'], (int) $row['ort'],
            (int) $row['ansprechpartner'], (int) $row['status'],
        );
    }
}
