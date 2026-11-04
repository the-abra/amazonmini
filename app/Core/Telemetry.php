<?php
// app/Core/Telemetry.php
// Central telemetry hub bridging backend PHP execution details to browser console.

declare(strict_types=1);

namespace App\Core;

class Telemetry
{
    private static float $startTime = 0.0;
    private static array $sqlQueries = [];
    private static array $events = [];
    private static ?string $matchedRoute = null;
    private static ?string $controllerAction = null;
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$startTime = microtime(true);
        self::$config = $config;
        self::$sqlQueries = [];
        self::$events = [];
    }

    public static function setRoute(string $route, string $controllerAction): void
    {
        self::$matchedRoute = $route;
        self::$controllerAction = $controllerAction;
    }

    public static function recordSql(string $sql, array $params = [], float $durationMs = 0.0): void
    {
        // Sanitize sensitive values if present
        $cleanParams = array_map(function ($val) {
            if (is_string($val) && strlen($val) > 120) {
                return substr($val, 0, 117) . '...';
            }
            return $val;
        }, $params);

        self::$sqlQueries[] = [
            'sql' => trim(preg_replace('/\s+/', ' ', $sql)),
            'params' => $cleanParams,
            'duration_ms' => round($durationMs, 2),
            'time' => date('H:i:s') . '.' . sprintf('%03d', (int)((microtime(true) - floor(microtime(true))) * 1000)),
        ];
    }

    public static function recordEvent(string $name, array $details = []): void
    {
        self::$events[] = [
            'event' => $name,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    public static function logTerminalEndpoint(string $method, string $route, int $statusCode = 200, string $extra = ''): void
    {
        // Keep dev terminal clean: only log state-changing or major actions, and errors
        $isStateChanging = in_array(strtoupper($method), ['POST', 'PUT', 'DELETE'], true);
        $isMajorRoute = str_starts_with($route, 'checkout') || str_starts_with($route, 'admin') || str_starts_with($route, 'courier');
        $isError = $statusCode >= 400;

        if ($isStateChanging || $isMajorRoute || $isError) {
            $durationMs = round((microtime(true) - (self::$startTime ?: microtime(true))) * 1000, 1);
            $msg = sprintf(
                "[APP] %s %-18s -> %d (%s ms)%s\n",
                strtoupper($method),
                $route ?: 'home',
                $statusCode,
                $durationMs,
                $extra ? " | {$extra}" : ''
            );
            file_put_contents('php://stderr', $msg);
        }
    }

    public static function getPayload(): array
    {
        $durationMs = round((microtime(true) - (self::$startTime ?: microtime(true))) * 1000, 2);
        $user = Auth::user();
        
        // Production check: hide telemetry in production environment
        $appEnv = self::$config['app_env'] ?? 'development';
        if ($appEnv === 'production') {
            return [
                'request' => [
                    'route' => self::$matchedRoute ?? 'home',
                    'duration_ms' => $durationMs,
                ],
                'database' => ['query_count' => 0, 'queries' => []],
                'events' => [],
            ];
        }

        return [
            'request' => [
                'route' => self::$matchedRoute ?? 'home',
                'action' => self::$controllerAction ?? 'Unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'duration_ms' => $durationMs,
                'user' => $user ? ['id' => $user['id'], 'role' => $user['role'], 'email' => $user['email']] : null,
                'memory_peak_kb' => round(memory_get_peak_usage() / 1024, 1),
            ],
            'database' => [
                'query_count' => count(self::$sqlQueries),
                'queries' => self::$sqlQueries,
            ],
            'events' => self::$events,
        ];
    }

    public static function renderJson(): string
    {
        return json_encode(self::getPayload(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
}
