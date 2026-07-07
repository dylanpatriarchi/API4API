<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/**
 * CRUD endpoints for beehives.
 */
final class BeehiveController extends Controller
{
    private const SELECT = 'SELECT beehive_id, name, latitude, longitude, esp_id FROM beehives';

    /**
     * GET /beehives — list every beehive.
     */
    public function index(): void
    {
        $rows = $this->db->query(self::SELECT . ' ORDER BY name')->fetchAll();

        Response::json(['data' => array_map([$this, 'present'], $rows)]);
    }

    /**
     * GET /beehives/{id} — fetch a single beehive.
     *
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params): void
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE beehive_id = :id');
        $stmt->execute(['id' => (int) $params['id']]);
        $row = $stmt->fetch();

        if ($row === false) {
            throw HttpException::notFound('Beehive not found');
        }

        Response::json(['data' => $this->present($row)]);
    }

    /**
     * POST /beehives — create a beehive.
     */
    public function store(Request $request): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO beehives (name, latitude, longitude, esp_id)
             VALUES (:name, :latitude, :longitude, :esp_id)'
        );
        $stmt->execute([
            'name'      => (string) $this->required($request, 'name'),
            'latitude'  => (float) $this->required($request, 'latitude'),
            'longitude' => (float) $this->required($request, 'longitude'),
            'esp_id'    => (int) $this->required($request, 'esp_id'),
        ]);

        Response::json(['data' => ['beehive_id' => (int) $this->db->lastInsertId()]], 201);
    }

    /**
     * PUT /beehives/{id} — update a beehive.
     *
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): void
    {
        $stmt = $this->db->prepare(
            'UPDATE beehives
                SET name = :name, latitude = :latitude, longitude = :longitude, esp_id = :esp_id
              WHERE beehive_id = :id'
        );
        $stmt->execute([
            'name'      => (string) $this->required($request, 'name'),
            'latitude'  => (float) $this->required($request, 'latitude'),
            'longitude' => (float) $this->required($request, 'longitude'),
            'esp_id'    => (int) $this->required($request, 'esp_id'),
            'id'        => (int) $params['id'],
        ]);

        if ($stmt->rowCount() === 0) {
            throw HttpException::notFound('Beehive not found');
        }

        Response::json(['message' => 'Beehive updated']);
    }

    /**
     * DELETE /beehives/{id} — remove a beehive.
     *
     * @param array<string, string> $params
     */
    public function destroy(Request $request, array $params): void
    {
        $stmt = $this->db->prepare('DELETE FROM beehives WHERE beehive_id = :id');
        $stmt->execute(['id' => (int) $params['id']]);

        if ($stmt->rowCount() === 0) {
            throw HttpException::notFound('Beehive not found');
        }

        Response::json(['message' => 'Beehive deleted']);
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
            'beehive_id' => (int) $row['beehive_id'],
            'name'       => $row['name'],
            'location'   => [
                'latitude'  => (float) $row['latitude'],
                'longitude' => (float) $row['longitude'],
            ],
            'esp' => [
                'esp_id' => (int) $row['esp_id'],
                'href'   => '/esp/' . (int) $row['esp_id'],
            ],
        ];
    }
}
