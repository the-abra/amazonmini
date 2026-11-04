<?php
// app/Core/Router.php
// Dispatcher mapping clean query routes (?route=...) to Controllers.

declare(strict_types=1);

namespace App\Core;

class Router
{
    private static array $routes = [
        // Storefront & Products
        'home' => ['App\Controllers\HomeController', 'index'],
        'product/view' => ['App\Controllers\ProductController', 'view'],
        'product/review' => ['App\Controllers\ProductController', 'submitReview'],

        // Authentication & Profile
        'auth/login' => ['App\Controllers\AuthController', 'login'],
        'auth/login-post' => ['App\Controllers\AuthController', 'loginPost'],
        'auth/demo-login' => ['App\Controllers\AuthController', 'demoLogin'],
        'auth/register' => ['App\Controllers\AuthController', 'register'],
        'auth/register-post' => ['App\Controllers\AuthController', 'registerPost'],
        'auth/logout' => ['App\Controllers\AuthController', 'logout'],
        'profile' => ['App\Controllers\ProfileController', 'index'],
        'profile/address-add' => ['App\Controllers\ProfileController', 'addressAdd'],
        'profile/address-delete' => ['App\Controllers\ProfileController', 'addressDelete'],
        'profile/address-default' => ['App\Controllers\ProfileController', 'addressSetDefault'],

        // Cart & Wishlist
        'cart' => ['App\Controllers\CartController', 'index'],
        'cart/add' => ['App\Controllers\CartController', 'add'],
        'cart/update' => ['App\Controllers\CartController', 'update'],
        'cart/remove' => ['App\Controllers\CartController', 'remove'],
        'wishlist' => ['App\Controllers\WishlistController', 'index'],
        'wishlist/toggle' => ['App\Controllers\WishlistController', 'toggle'],

        // Stripe Checkout & Orders
        'checkout' => ['App\Controllers\CheckoutController', 'index'],
        'checkout/create' => ['App\Controllers\CheckoutController', 'createSession'],
        'checkout/success' => ['App\Controllers\CheckoutController', 'success'],
        'checkout/cancel' => ['App\Controllers\CheckoutController', 'cancel'],
        'order/list' => ['App\Controllers\OrderController', 'index'],
        'order/view' => ['App\Controllers\OrderController', 'view'],
        'order/cancel' => ['App\Controllers\OrderController', 'cancel'],

        // Stripe asynchronous notifications & health probe (no session / CSRF)
        'webhook/stripe' => ['App\Controllers\WebhookController', 'stripe'],
        'health' => ['App\Controllers\HealthController', 'check'],
        
        // Cargo Tracking
        'tracking/track' => ['App\Controllers\TrackingController', 'track'],

        // Admin Management Portal
        'admin' => ['App\Controllers\AdminController', 'dashboard'],
        'admin/dashboard' => ['App\Controllers\AdminController', 'dashboard'],
        'admin/products' => ['App\Controllers\AdminController', 'products'],
        'admin/product-save' => ['App\Controllers\AdminController', 'productSave'],
        'admin/product-delete' => ['App\Controllers\AdminController', 'productDelete'],
        'admin/orders' => ['App\Controllers\AdminController', 'orders'],
        'admin/order-pack' => ['App\Controllers\AdminController', 'packOrder'],
        'admin/webhooks' => ['App\Controllers\AdminController', 'webhooks'],
        'admin/webhook-simulate' => ['App\Controllers\AdminController', 'webhookSimulate'],

        // Courier Logistics Portal
        'courier' => ['App\Controllers\CourierController', 'dashboard'],
        'courier/dashboard' => ['App\Controllers\CourierController', 'dashboard'],
        'courier/update-status' => ['App\Controllers\CourierController', 'updateStatus'],
    ];

    /** Machine-to-machine endpoints authenticate by signature, not by browser CSRF token. */
    private const CSRF_EXEMPT = ['webhook/stripe', 'auth/login-post'];

    public static function dispatch(string $route): void
    {
        $route = trim($route, '/');
        if ($route === '') {
            $route = 'home';
        }

        if (!isset(self::$routes[$route])) {
            http_response_code(404);
            Telemetry::setRoute($route, 'NotFound');
            Telemetry::logTerminalEndpoint($_SERVER['REQUEST_METHOD'] ?? 'GET', $route, 404);
            View::render('home/error', [
                'statusCode' => 404,
                'title' => 'Page Not Found',
                'message' => "The requested route '{$route}' does not exist.",
            ]);
            return;
        }

        [$controllerClass, $method] = self::$routes[$route];
        Telemetry::setRoute($route, "{$controllerClass}@{$method}");

        // CSRF Check for POST requests (except webhook/public endpoints if any)
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !in_array($route, self::CSRF_EXEMPT, true)) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Csrf::validate($token)) {
                http_response_code(403);
                Telemetry::logTerminalEndpoint('POST', $route, 403, 'CSRF token mismatch');
                if (is_ajax()) {
                    View::json([
                        'success' => false,
                        'message' => 'CSRF verification failed or session expired. Please refresh the page.',
                    ], 403);
                }
                View::render('home/error', [
                    'statusCode' => 403,
                    'title' => 'Page Expired',
                    'message' => 'CSRF verification failed or session expired. Please refresh the page and try again.',
                ]);
                return;
            }
        }

        try {
            $controller = new $controllerClass();
            $controller->$method();
            Telemetry::logTerminalEndpoint($_SERVER['REQUEST_METHOD'] ?? 'GET', $route, http_response_code() ?: 200);
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('[Exception] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            Telemetry::logTerminalEndpoint($_SERVER['REQUEST_METHOD'] ?? 'GET', $route, 500, $e->getMessage());
            if (is_ajax()) {
                View::json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
            View::render('home/error', [
                'statusCode' => 500,
                'title' => 'Application Error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
