<?php
// app/Controllers/HealthController.php
// Liveness/readiness probe used by Docker healthchecks and uptime monitors.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class HealthController
{
    public function check(): void
    {
        $dbOk = false;
        $dbMs = null;
        try {
            $start = microtime(true);
            Database::fetchOne('SELECT 1 AS ok');
            $dbMs = round((microtime(true) - $start) * 1000, 2);
            $dbOk = true;
        } catch (\Throwable $e) {
            error_log('[health] database check failed: ' . $e->getMessage());
        }

        View::json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'php' => PHP_VERSION,
            'database' => ['ok' => $dbOk, 'latency_ms' => $dbMs],
            'time' => gmdate('c'),
        ], $dbOk ? 200 : 503);
    }
}
