<?php
/** Account-only persistence port: member master data and permissions stay separate. */
declare(strict_types=1);

namespace Vereinssystem\Accounts;

interface AccountRepository
{
    /** @return array{username:string,email:string,mobile:string}|null */
    public function profile(int $memberId): ?array;

    /** Update ONLY the logged-in member; the known hash prevents stale writes. */
    public function updateProfile(int $memberId, string $expectedHash, string $username, string $email, string $mobile): bool;

    /** Atomic compare-and-swap: another password change cannot be overwritten. */
    public function changePassword(int $memberId, string $expectedHash, string $newHash): bool;
}
