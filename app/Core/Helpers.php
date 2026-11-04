<?php
// app/Core/Helpers.php
// Global template helper functions for clean view code.

declare(strict_types=1);

if (!function_exists('e')) {
    function e(mixed $str): string
    {
        return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $route = 'home', array $params = []): string
    {
        $query = array_merge(['route' => $route], $params);
        return 'index.php?' . http_build_query($query);
    }
}

if (!function_exists('money')) {
    function money(mixed $amount, string $symbol = '$'): string
    {
        $val = is_numeric($amount) ? (float)$amount : 0.0;
        return '<span class="notranslate" translate="no">' . $symbol . number_format($val, 2) . '</span>';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\Csrf::field();
    }
}

if (!function_exists('is_ajax')) {
    function is_ajax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }
}

if (!function_exists('page_url')) {
    /** Current catalog URL with a different ?page= while keeping search/filter parameters. */
    function page_url(int $page, string $route = 'home'): string
    {
        $params = array_diff_key($_GET, ['route' => 1, 'page' => 1]);
        if ($page > 1) {
            $params['page'] = $page;
        }
        return url($route, $params);
    }
}

if (!function_exists('status_badge')) {
    /** Consistent coloured pill for order / payment / shipment states. */
    function status_badge(string $value, ?string $label = null): string
    {
        static $tones = [
            'paid' => 'success', 'delivered' => 'success',
            'pending' => 'warning', 'placed' => 'warning', 'packed' => 'info', 'awaiting_pickup' => 'info',
            'shipped' => 'info', 'picked_up' => 'info', 'in_transit' => 'info', 'out_for_delivery' => 'info',
            'failed' => 'danger', 'delivery_failed' => 'danger', 'cancelled' => 'danger',
            'returned' => 'danger', 'returned_to_sender' => 'danger', 'refunded' => 'neutral',
        ];
        $tone = $tones[$value] ?? 'neutral';
        $text = $label ?? ucwords(str_replace('_', ' ', $value));
        return '<span class="status-pill status-' . $tone . '">' . e($text) . '</span>';
    }
}
