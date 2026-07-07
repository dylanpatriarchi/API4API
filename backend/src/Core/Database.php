<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Thin wrapper around a lazily-created, shared PDO connection.
 */
final class Database
{
    private static ?PDO $connection = null;

    /**
     * Return the shared PDO connection, creating it on first use.
     */
    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host    = (string) Config::get('database.host', '127.0.0.1');
        $port    = (int) Config::get('database.port', 3306);
        $name    = (string) Config::get('database.name', '');
        $charset = (string) Config::get('database.charset', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

        self::$connection = new PDO(
            $dsn,
            (string) Config::get('database.user', ''),
            (string) Config::get('database.password', ''),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        return self::$connection;
    }
}
