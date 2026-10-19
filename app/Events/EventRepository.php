<?php
/**
 * Vertrag für Veranstaltungstermine und frei definierbare Aufgaben.
 * Eine Aufgabe kann mehreren aktiven Vereinsmitgliedern zugeordnet sein.
 * Keine Abhängigkeit von historischen Standdienst- oder Kuchendienstfeldern.
 */
declare(strict_types=1);
namespace Vereinssystem\Events;

use Vereinssystem\Models\{Event,EventTask};

interface EventRepository
{
    /** @return list<array<string,mixed>> */
    public function listing(bool $includeInactive = false): array;
    /** @return array<string,mixed>|null */
    public function find(int $id): ?array;
    public function save(Event $event): int;
    public function setStatus(int $id, int $status): bool;

    /** @return list<array<string,mixed>> Aufgaben mit 'members' als Array */
    public function tasks(int $eventId): array;
    /** @return array<string,mixed>|null */
    public function task(int $eventId, int $taskId): ?array;
    /** @return list<array{id:int,name:string}> Nur aktive Mitglieder */
    public function activeMembers(): array;
    /** @return list<array{id:int,name:string}> Aktive Orte für die Veranstaltung */
    public function activePlaces(): array;

    /**
     * @throws \DomainException Bei Konflikten, inaktiven Mitgliedern und Veranstaltungen.
     */
    public function saveTask(EventTask $task, int $actorId): int;

    /** Historische Aufgabe deaktivieren, Zuordnungen bleiben dokumentiert. */
    public function archiveTask(int $eventId, int $taskId): void;
}
