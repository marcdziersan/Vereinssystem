<?php
/**
 * Immutable member read model.
 *
 * Contains the fields explicitly needed by the MVC member module. Banking
 * details, password hashes and hidden legacy counters are NOT loaded.
 */
declare(strict_types=1);

namespace Vereinssystem\Members;

final class Member
{
    public function __construct(
        public readonly int $id,
        public readonly string $number,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $birthday,
        public readonly string $joinedAt,
        public readonly string $email,
        public readonly string $mobile,
        public readonly int $gender,
        public readonly int $membership,
        public readonly int $billing,
        public readonly int $addressId,
        public readonly int $parent1,
        public readonly int $parent2,
        public readonly string $notes,
        public readonly string $username,
        public readonly int $rights,
        public readonly int $status,
    ) {}

    public function fullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /** Form defaults are kept separate from authentication and database columns. */
    public function editable(): array
    {
        return [
            'number' => $this->number,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'birthday' => $this->birthday,
            'joined_at' => $this->joinedAt,
            'gender' => (string) $this->gender,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'membership' => (string) $this->membership,
            'billing' => (string) $this->billing,
            'address_id' => (string) $this->addressId,
            'parent1' => (string) $this->parent1,
            'parent2' => (string) $this->parent2,
            'notes' => $this->notes,
            'username' => $this->username,
        ];
    }
}
