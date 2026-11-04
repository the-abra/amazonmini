<?php
/**
 * Modular PHP E-Commerce & Cargo Tracking Platform (Amazon Mini Clone)
 * 
 * Entrypoint Front Controller.
 * Kept in root alongside Makefile, LICENSE, and README.md.
 */

declare(strict_types=1);

// 1. Error Handling Configuration
ini_set('display_errors', '1');
error_reporting(E_ALL);

// 2. Configure Session Security
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
}

// 2b. Baseline security headers
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header_remove('X-Powered-By');
}

// 3. Load Autoloader & Environment Configuration
require_once __DIR__ . '/app/Core/Bootstrap.php';
$config = require __DIR__ . '/config/env.php';

// 4. Initialize Core Singletons
\App\Core\Database::configure($config);
\App\Core\Telemetry::init($config);
// Machine endpoints (Stripe webhook, health probe) must not create sessions.
$route = $_GET['route'] ?? 'home';
if (!in_array($route, ['webhook/stripe', 'health'], true)) {
    \App\Core\Auth::start();
}

// 5. Share Global Template Variables
\App\Core\View::share('appName', $config['app_name'] ?? 'Amazon Mini Clone');
\App\Core\View::share('currencySymbol', $config['currency_symbol'] ?? '$');

// 6. Dispatch Request Route
\App\Core\Router::dispatch($route);
