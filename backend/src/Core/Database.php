<?php

namespace Src\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $this->connection = $this->createConnection();
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }

    private function createConnection(): PDO
    {
        try {
            return new PDO(
                $this->getDSN(),
                $this->getUsername(),
                $this->getPassword(),
                $this->getOptions()
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Database connection failed: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    private function getDSN(): string
    {
        return sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_NAME'] ?? ''
        );
    }

    private function getUsername(): string
    {
        return $_ENV['DB_USER'] ?? '';
    }

    private function getPassword(): string
    {
        return $_ENV['DB_PASS'] ?? '';
    }

    private function getOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false
        ];
    }

    private function __clone() {}
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize database connection");
    }
}
