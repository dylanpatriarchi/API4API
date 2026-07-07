<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/**
 * Read and update the alert thresholds for each metric.
 */
final class ThresholdController extends Controller
{
    /**
     * GET /thresholds — list every configured threshold.
     */
    public function index(): void
    {
        $rows = $this->db
            ->query('SELECT metric, threshold_value FROM thresholds ORDER BY metric')
            ->fetchAll();

        Response::json(['data' => array_map([$this, 'present'], $rows)]);
    }

    /**
     * GET /thresholds/{metric} — fetch a single threshold.
     *
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params): void
    {
        $stmt = $this->db->prepare(
            'SELECT metric, threshold_value FROM thresholds WHERE metric = :metric'
        );
        $stmt->execute(['metric' => $params['metric']]);
        $row = $stmt->fetch();

        if ($row === false) {
            throw HttpException::notFound('Threshold not found');
        }

        Response::json(['data' => $this->present($row)]);
    }

    /**
     * PUT /thresholds/{metric} — update a threshold value.
     *
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params): void
    {
        $stmt = $this->db->prepare(
            'UPDATE thresholds SET threshold_value = :value WHERE metric = :metric'
        );
        $stmt->execute([
            'value'  => (float) $this->required($request, 'threshold_value'),
            'metric' => $params['metric'],
        ]);

        if ($stmt->rowCount() === 0) {
            throw HttpException::notFound('Threshold not found');
        }

        Response::json(['message' => 'Threshold updated']);
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
            'metric'          => $row['metric'],
            'threshold_value' => (float) $row['threshold_value'],
        ];
    }
}
