<?php
/**
 * Immutable configuration wrapper. Only explicitly provided keys are read;
 * no user-controlled path is ever used as a PHP include target.
 */
declare(strict_types=1);

namespace Vereinssystem\Core;

use InvalidArgumentException;

final class Config
{
    /** @param array<string, mixed> $values */
    public function __construct(private readonly array $values) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function requireString(string $key): string
    {
        $value = $this->values[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException("Configuration entry '{$key}' is missing.");
        }
        return $value;
    }
}
