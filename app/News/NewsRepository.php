<?php
/** Stable persistence interface, allowing deterministic in-memory tests. */
declare(strict_types=1);
namespace Vereinssystem\News;
use Vereinssystem\Models\NewsPost;

interface NewsRepository
{
    /** @return list<array<string,mixed>> Returns only posts visible at the given rights level. */
    public function listing(int $rights, string $search='', int $page=1, int $perPage=15): array;
    public function countVisible(int $rights, string $search=''): int;
    /** Read any status for administrative editing; display permission is separate. */
    public function find(int $id): ?NewsPost;
    /** Return the original author display name without leaking user information. */
    public function authorName(int $id): string;
    /** Create/update; preserve author and original creation timestamp. */
    public function save(NewsPost $post): int;
    /** Soft-archive only an active post, never delete or reset audit fields. */
    public function archive(int $id, int $editorId, bool $isAdmin): bool;
    public function restore(int $id): bool;
    /** @return list<NewsPost> Most recently changed, archived items for administrator review. */
    public function archived(int $limit=100): array;
}
