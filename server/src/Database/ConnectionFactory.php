<?php
declare(strict_types=1);

namespace Tna\Database;

use PDO;
use Tna\Config\DatabaseConfig;

final class ConnectionFactory
{
    private ?PDO $connection = null;

    public function __construct(private readonly DatabaseConfig $config)
    {
    }

    public function getConnection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $this->config->host, $this->config->port, $this->config->name, $this->config->charset ?: 'utf8mb4');
        $this->connection = new PDO($dsn, $this->config->user, $this->config->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $this->connection;
    }
}
