<?php
/**
 * One freely named activity: task, date/time, location and many members.
 * There is intentionally no type, stand slot, cake count or mandatory 45-minute limit.
 */
declare(strict_types=1);
namespace Vereinssystem\Models;

final readonly class EventTask
{
    /** @param list<TaskAssignment> $assignments */
    public function __construct(
        public ?int $id,
        public int $eventId,
        public string $title,
        public string $description,
        public string $place,
        public int $startsAt,
        public int $endsAt,
        public array $assignments,
    ) {}

    /** @return list<int> */
    public function memberIds(): array
    {
        return array_map(static fn(TaskAssignment $item): int => $item->memberId, $this->assignments);
    }
}
