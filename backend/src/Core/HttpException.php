<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Exception carrying an HTTP status code, translated into an error response.
 */
final class HttpException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 500)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self($message, 404);
    }

    public static function badRequest(string $message = 'Bad request'): self
    {
        return new self($message, 400);
    }
}
