<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use PDO;

/**
 * Base controller providing shared database access and validation helpers.
 */
abstract class Controller
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Fetch a required field from the request body, or fail with 400.
     */
    protected function required(Request $request, string $key): mixed
    {
        $value = $request->input($key);

        if ($value === null || $value === '') {
            throw HttpException::badRequest(sprintf('Missing required field: %s', $key));
        }

        return $value;
    }
}
