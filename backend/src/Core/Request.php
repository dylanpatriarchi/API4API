<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable view over the current HTTP request.
 */
final class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, mixed>  $body
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
    ) {
    }

    /**
     * Build a Request from PHP superglobals and the raw input stream.
     */
    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim(rawurldecode($path), '/');

        $rawBody = file_get_contents('php://input') ?: '';
        $decoded = json_decode($rawBody, true);
        $body    = is_array($decoded) ? $decoded : [];

        /** @var array<string, string> $query */
        $query = $_GET;

        return new self($method, $path, $query, $body);
    }

    /**
     * Return a query-string parameter, or the default when absent.
     */
    public function query(string $key, ?string $default = null): ?string
    {
        $value = $this->query[$key] ?? $default;

        return $value === null ? null : (string) $value;
    }

    /**
     * Return a decoded JSON body field, or the default when absent.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }
}
