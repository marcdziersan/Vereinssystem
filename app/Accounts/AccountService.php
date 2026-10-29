<?php
/** Validation and credential rules for self-service account management. */
declare(strict_types=1);

namespace Vereinssystem\Accounts;

use Vereinssystem\Auth\User;

final class AccountService
{
    public function __construct(private readonly AccountRepository $accounts) {}

    /** @return array{username:string,email:string,mobile:string}|null */
    public function profile(User $user): ?array
    {
        return $this->accounts->profile($user->id);
    }

    /** Only the user's own record can be changed. Never accepts rights/status/id from the form. */
    public function updateProfile(User $user, string $currentPassword, string $username, string $email, string $mobile): void
    {
        self::requireCurrentPassword($user, $currentPassword);
        $username = trim($username);
        $email = trim($email);
        $mobile = trim($mobile);
        if (!preg_match('/^[A-Za-z0-9_.@-]{3,100}$/D', $username)) {
            throw new \DomainException('Benutzername: 3–100 Zeichen aus Buchstaben, Zahlen, Punkt, @, _ und -.');
        }
        if (strlen($email) > 254 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            throw new \DomainException('Bitte eine gültige E-Mail-Adresse eingeben (max. 254 Zeichen).');
        }
        if (strlen($mobile) > 60 || preg_match('/[\x00-\x1F\x7F]/', $mobile)) {
            throw new \DomainException('Mobilnummer ungültig (max. 60 Zeichen).');
        }
        if (!$this->accounts->updateProfile($user->id, $user->passwordHash, $username, $email, $mobile)) {
            throw new \DomainException('Profil wurde zwischenzeitlich geändert. Bitte erneut anmelden.');
        }
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword, string $confirmation): void
    {
        self::requireCurrentPassword($user, $currentPassword);
        if ($newPassword !== $confirmation) {
            throw new \DomainException('Die neuen Passwörter stimmen nicht überein.');
        }
        if (strlen($newPassword) < 12 || strlen($newPassword) > 4096) {
            throw new \DomainException('Neues Passwort: 12–4096 Zeichen.');
        }
        if (hash_equals($currentPassword, $newPassword)) {
            throw new \DomainException('Das neue Passwort muss sich vom bisherigen unterscheiden.');
        }
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if (!$this->accounts->changePassword($user->id, $user->passwordHash, $newHash)) {
            throw new \DomainException('Passwort konnte nicht geändert werden. Bitte erneut anmelden.');
        }
    }

    private static function requireCurrentPassword(User $user, string $password): void
    {
        if (strlen($password) > 4096 || $password === '') {
            throw new \DomainException('Aktuelles Passwort ist nicht korrekt.');
        }
        $hash = $user->passwordHash;
        $valid = password_verify($password, $hash);
        // A historical MD5 record remains editable if it predates the first login migration.
        if (!$valid && preg_match('/^[a-f0-9]{32}$/iD', $hash)) {
            $valid = hash_equals(strtolower($hash), md5($password));
        }
        if (!$valid) throw new \DomainException('Aktuelles Passwort ist nicht korrekt.');
    }
}