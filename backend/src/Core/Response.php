<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Helpers for emitting JSON HTTP responses.
 */
final class Response
{
    /**
     * Send a JSON payload with the given status code and terminate output.
     *
     * @param array<string, mixed>|list<mixed> $payload
     */
    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Send a standardized error envelope.
     */
    public static function error(string $message, int $status): void
    {
        self::json(['error' => $message], $status);
    }
}
