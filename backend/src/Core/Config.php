<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Loads and exposes the application configuration.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    /**
     * Load configuration from the given file once.
     */
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException(
                'Configuration file not found. Copy config/config.example.php to config/config.php.'
            );
        }

        /** @var array<string, mixed> $items */
        $items = require $path;
        self::$items = $items;
    }

    /**
     * Retrieve a configuration value using dot notation (e.g. "database.host").
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
