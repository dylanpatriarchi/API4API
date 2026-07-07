<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Support\Mailer;

/**
 * Read and ingest sensor measurements coming from the hives.
 */
final class MeasurementController extends Controller
{
    private const METRICS = ['weight', 'temperature', 'humidity', 'noise_level'];

    private const SELECT =
        'SELECT m.measurement_id, m.weight, m.temperature, m.humidity, m.noise_level,
                m.recorded_at, b.name AS beehive_name, b.beehive_id, e.esp_id
           FROM measurements m
           JOIN beehives b ON m.beehive_id = b.beehive_id
           JOIN esp e      ON b.esp_id = e.esp_id';

    /**
     * GET /measurements — list measurements, newest first.
     */
    public function index(): void
    {
        $rows = $this->db
            ->query(self::SELECT . ' ORDER BY m.recorded_at DESC')
            ->fetchAll();

        Response::json(['data' => array_map([$this, 'present'], $rows)]);
    }

    /**
     * GET /measurements/{id} — fetch a single measurement.
     *
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params): void
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE m.measurement_id = :id');
        $stmt->execute(['id' => (int) $params['id']]);
        $row = $stmt->fetch();

        if ($row === false) {
            throw HttpException::notFound('Measurement not found');
        }

        Response::json(['data' => $this->present($row)]);
    }

    /**
     * POST /measurements — ingest a new measurement and raise alerts.
     */
    public function store(Request $request): void
    {
        $measurement = [
            'weight'      => (float) $this->required($request, 'weight'),
            'temperature' => (float) $this->required($request, 'temperature'),
            'humidity'    => (float) $this->required($request, 'humidity'),
            'noise_level' => (float) $this->required($request, 'noise_level'),
            'beehive_id'  => (int) $this->required($request, 'beehive_id'),
            'recorded_at' => (string) $this->required($request, 'recorded_at'),
        ];

        $stmt = $this->db->prepare(
            'INSERT INTO measurements
                (weight, temperature, humidity, noise_level, beehive_id, recorded_at)
             VALUES
                (:weight, :temperature, :humidity, :noise_level, :beehive_id, :recorded_at)'
        );
        $stmt->execute($measurement);

        $this->raiseThresholdAlerts($measurement);

        Response::json(
            ['data' => ['measurement_id' => (int) $this->db->lastInsertId()]],
            201
        );
    }

    /**
     * Compare a measurement against configured thresholds and e-mail on breach.
     *
     * @param array<string, float|int|string> $measurement
     */
    private function raiseThresholdAlerts(array $measurement): void
    {
        $thresholds = $this->db
            ->query('SELECT metric, threshold_value FROM thresholds')
            ->fetchAll();

        foreach ($thresholds as $threshold) {
            $metric = (string) $threshold['metric'];

            if (!in_array($metric, self::METRICS, true)) {
                continue;
            }

            $value = (float) $measurement[$metric];
            $limit = (float) $threshold['threshold_value'];

            if ($value > $limit) {
                Mailer::sendThresholdAlert($metric, $value, $limit);
            }
        }
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
            'measurement_id' => (int) $row['measurement_id'],
            'weight'         => (float) $row['weight'],
            'temperature'    => (float) $row['temperature'],
            'humidity'       => (float) $row['humidity'],
            'noise_level'    => (float) $row['noise_level'],
            'recorded_at'    => $row['recorded_at'],
            'beehive'        => [
                'beehive_id' => (int) $row['beehive_id'],
                'name'       => $row['beehive_name'],
                'esp'        => [
                    'esp_id' => (int) $row['esp_id'],
                    'href'   => '/esp/' . (int) $row['esp_id'],
                ],
            ],
        ];
    }
}
