<?php
/** Immutable account projection: intentionally excludes banking and private member fields. */
declare(strict_types=1);

namespace Vereinssystem\Auth;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $memberNumber,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $passwordHash,
        public readonly int $rights,
        public readonly int $status,
    ) {}

    public function displayName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function active(): bool
    {
        return $this->status === 1 && $this->rights >= 1 && $this->rights <= 5;
    }
}
