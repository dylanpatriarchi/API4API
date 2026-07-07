<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/**
 * CRUD endpoints for ESP32 controller boards.
 */
final class EspController extends Controller
{
    /**
     * GET /esp — list every registered board.
     */
    public function index(): void
    {
        $rows = $this->db
            ->query('SELECT esp_id, dev_kit, pin_count FROM esp ORDER BY esp_id')
            ->fetchAll();

        Response::json(['data' => array_map([$this, 'present'], $rows)]);
    }

    /**
     * GET /esp/{id} — fetch a single board.
     *
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params): void
    {
        $stmt = $this->db->prepare('SELECT esp_id, dev_kit, pin_count FROM esp WHERE esp_id = :id');
        $stmt->execute(['id' => (int) $params['id']]);
        $row = $stmt->fetch();

        if ($row === false) {
            throw HttpException::notFound('ESP board not found');
        }

        Response::json(['data' => $this->present($row)]);
    }

    /**
     * POST /esp — register a new board.
     */
    public function store(Request $request): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO esp (dev_kit, pin_count) VALUES (:dev_kit, :pin_count)'
        );
        $stmt->execute([
            'dev_kit'   => (string) $this->required($request, 'dev_kit'),
            'pin_count' => (int) $this->required($request, 'pin_count'),
        ]);

        Response::json(['data' => ['esp_id' => (int) $this->db->lastInsertId()]], 201);
    }

    /**
     * PUT /esp/{id} — update an existing board.
     *
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): void
    {
        $stmt = $this->db->prepare(
            'UPDATE esp SET dev_kit = :dev_kit, pin_count = :pin_count WHERE esp_id = :id'
        );
        $stmt->execute([
            'dev_kit'   => (string) $this->required($request, 'dev_kit'),
            'pin_count' => (int) $this->required($request, 'pin_count'),
            'id'        => (int) $params['id'],
        ]);

        if ($stmt->rowCount() === 0) {
            throw HttpException::notFound('ESP board not found');
        }

        Response::json(['message' => 'ESP board updated']);
    }

    /**
     * DELETE /esp/{id} — remove a board.
     *
     * @param array<string, string> $params
     */
    public function destroy(Request $request, array $params): void
    {
        $stmt = $this->db->prepare('DELETE FROM esp WHERE esp_id = :id');
        $stmt->execute(['id' => (int) $params['id']]);

        if ($stmt->rowCount() === 0) {
            throw HttpException::notFound('ESP board not found');
        }

        Response::json(['message' => 'ESP board deleted']);
    }

    /**
     * Shape a database row into the public JSON representation.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function present(array $row): array
    {
        return [
            'esp_id'    => (int) $row['esp_id'],
            'dev_kit'   => $row['dev_kit'],
            'pin_count' => (int) $row['pin_count'],
        ];
    }
}
