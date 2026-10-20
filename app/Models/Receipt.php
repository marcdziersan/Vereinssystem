<?php
/** Immutable private receipt metadata. The original filename is display-only. */
declare(strict_types=1);
namespace Vereinssystem\Models;
final class Receipt
{
    public function __construct(
        public readonly int $id,
        public readonly int $entryId,
        public readonly string $originalName,
        public readonly string $storageName,
        public readonly string $mime,
        public readonly int $size,
        public readonly string $checksum,
        public readonly int $uploadedBy,
        public readonly string $createdAt,
    ) {}
}
