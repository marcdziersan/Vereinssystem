<?php
/**
 * Member business rules independent of HTTP, sessions and SQL.
 *
 * The membership form never assigns access rights or modifies passwords of
 * existing accounts: those operations belong exclusively to the access module.
 */
declare(strict_types=1);

namespace Vereinssystem\Members;

use Vereinssystem\Auth\User;

final class MemberService
{
    public function __construct(private readonly MemberRepository $members) {}

    /** @param array<string,string> $input @return array<string,string|int> */
    public function validate(array $input, bool $creating): array
    {
        $errors = [];
        $values = [];
        foreach (['number'=>25, 'first_name'=>100, 'last_name'=>100, 'mobile'=>60,
                  'notes'=>4000, 'username'=>100, 'email'=>254] as $key => $max) {
            $value = trim($input[$key] ?? '');
            if (strlen($value) > $max) $errors[$key] = 'Eingabe zu lang (max. ' . $max . ' Zeichen/Bytes).';
            $values[$key] = $value;
        }
        foreach (['number'=>'Mitgliedsnummer', 'first_name'=>'Vorname', 'last_name'=>'Nachname'] as $key=>$label) {
            if ($values[$key] === '') $errors[$key] = $label . ' ist erforderlich.';
        }
        if (!preg_match('/^[\p{L}\p{N}_.\/-]{1,25}$/uD', $values['number'])) {
            $errors['number'] = 'Mitgliedsnummer: Buchstaben, Zahlen, Punkt, Bindestrich, Unterstrich oder Schrägstrich.';
        }
        if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        }
        if ($creating && !preg_match('/^[A-Za-z0-9_.@-]{3,100}$/D', $values['username'])) {
            $errors['username'] = 'Benutzername: 3–100 Zeichen aus Buchstaben, Ziffern, Punkt, @, _ und -.';
        }
        foreach (['birthday'=>'Geburtsdatum', 'joined_at'=>'Eintrittsdatum'] as $key=>$label) {
            $date = trim($input[$key] ?? '');
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date
                || $parsed < new \DateTimeImmutable('1900-01-01')
                || $parsed > new \DateTimeImmutable('today')) {
                $errors[$key] = $label . ' ist ungültig.';
            }
            $values[$key] = $date;
        }
        foreach (['gender'=>[0,2], 'membership'=>[0,2], 'billing'=>[0,1],
                  'address_id'=>[0,2147483647], 'parent1'=>[0,2147483647], 'parent2'=>[0,2147483647]] as $key=>$range) {
            $number = filter_var($input[$key] ?? '', FILTER_VALIDATE_INT);
            if ($number === false || $number < $range[0] || $number > $range[1]) {
                $errors[$key] = 'Bitte einen zulässigen Wert auswählen.';
            } else {
                $values[$key] = $number;
            }
        }
        if ($creating) {
            $password = $input['password'] ?? '';
            if (strlen($password) < 12 || strlen($password) > 4096) {
                $errors['password'] = 'Initialpasswort: 12–4096 Zeichen.';
            }
        }
        if ($errors) throw new MemberValidationException($errors);
        // Login names must never be changed by a general member edit.
        if (!$creating) unset($values['username']);
        return $values;
    }

    /** @param array<string,string> $input */
    public function add(array $input): int
    {
        $data = $this->validate($input, true);
        return $this->members->create($data, password_hash($input['password'], PASSWORD_DEFAULT));
    }

    /** @param array<string,string> $input */
    public function edit(int $id, array $input): bool
    {
        if ($id < 1 || $this->members->byId($id) === null) return false;
        return $this->members->update($id, $this->validate($input, false));
    }

    public function status(int $id, int $newStatus, User $actor): bool
    {
        if ($id < 1 || $actor->id === $id || !in_array($newStatus, [1,2], true)) return false;
        $target = $this->members->byId($id);
        // A privileged administrator cannot be disabled via the member module.
        if (!$target || $target->rights === 5 || $target->status === $newStatus) return false;
        return $this->members->setStatus($id, $newStatus);
    }
}
