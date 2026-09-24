<?php

declare(strict_types=1);

namespace OverDark\Core\Database;

use OverDark\Core\Config\Env;
use PDO;
use PDOException;

final class ConnectionFactory
{
    /**
     * @param array{host: string, port: int, database: string, username: string, password: string, lock_wait_timeout?: int} $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public static function fromEnv(): self
    {
        return new self([
            'host' => (string) Env::get('DB_HOST', 'db'),
            'port' => (int) Env::get('DB_PORT', 3306),
            'database' => (string) Env::get('DB_DATABASE', 'overdark'),
            'username' => (string) Env::get('DB_USERNAME', 'overdark'),
            'password' => (string) Env::get('DB_PASSWORD', ''),
            'lock_wait_timeout' => (int) Env::get('DB_LOCK_WAIT_TIMEOUT', 3),
        ]);
    }

    public function database(): string
    {
        return $this->config['database'];
    }

    public function create(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
        );

        try {
            $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
            ]);
        } catch (PDOException $e) {
            throw new DatabaseUnavailable($this->config['host'] . ':' . $this->config['port'], $this->config['database'], $e);
        }

        // Falhar rápido: uma requisição web não deve ficar 50s (padrão do MySQL)
        // esperando um registro travado por outra rotina.
        $pdo->exec(sprintf('SET SESSION innodb_lock_wait_timeout = %d', max(1, $this->config['lock_wait_timeout'] ?? 3)));

        return $pdo;
    }
}
