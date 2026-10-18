<?php
/**
 * Optional PDO factory. The kernel must boot WITHOUT MySQL or credentials.
 * Later repositories will depend on this factory, never on global SQL helpers.
 */
declare(strict_types=1);

namespace Vereinssystem\Core;

use InvalidArgumentException;
use PDO;

final class Database
{
    private ?PDO $connection = null;

    /** @param array{host: string, database: string, username: string, password: string, port?: int} $settings */
    public function __construct(private readonly array $settings) {}

    public function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }
        foreach (['host', 'database', 'username', 'password'] as $required) {
            if (!isset($this->settings[$required]) || !is_string($this->settings[$required])) {
                throw new InvalidArgumentException('Invalid database configuration.');
            }
        }
        $host = $this->settings['host'];
        $name = $this->settings['database'];
        $port = $this->settings['port'] ?? 3306;
        // A DSN is configuration, not untrusted form input.
        if (!preg_match('/^[A-Za-z0-9.:-]+$/D', $host) || !preg_match('/^[A-Za-z0-9_-]+$/D', $name) || $port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Invalid database connection parameters.');
        }
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $this->connection = new PDO($dsn, $this->settings['username'], $this->settings['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $this->connection;
    }
}
