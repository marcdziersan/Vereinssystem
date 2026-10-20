<?php
/** Metadata for a privately stored receipt. File bytes never appear in a public URL. */
declare(strict_types=1);
namespace Vereinssystem\Models;
final class CashReceipt
{
    public function __construct(
        public readonly int $id,
        public readonly int $entryId,
        public readonly string $originalName,
        public readonly string $mime,
        public readonly int $size,
        public readonly string $checksum,
        public readonly string $storedName,
    ) {}
}
