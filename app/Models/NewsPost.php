<?php
/** Immutable bulletin post. Database/HTTP/UI are not dependencies of this model. */
declare(strict_types=1);
namespace Vereinssystem\Models;

final readonly class NewsPost
{
    public function __construct(
        public ?int $id,
        public string $title,
        public string $content,
        public int $minRight,
        public int $authorId,
        public int $createdAt,
        public int $changedAt,
        public int $status=1,
    ) {}

    /** @param array<string,mixed> $row Legacy news table projection. */
    public static function fromRow(array $row): self
    {
        return new self((int)$row['news_id'], (string)$row['title'], (string)$row['content'],
            (int)$row['minright'], (int)$row['author'], (int)$row['created'],
            (int)$row['lastchange'], (int)$row['status']);
    }
}
