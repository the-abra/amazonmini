<?php
// app/Controllers/AdminController.php
// Store administration, product management, and order packaging.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use App\Core\Database;
use App\Services\StripeWebhookService;

class AdminController
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function dashboard(): void
    {
        $metrics = Order::getMetrics();
        $recentOrders = Order::all(['limit' => 5]);
        $couriers = User::getCouriers();

        View::render('admin/dashboard', [
            'pageTitle' => 'Admin Management Dashboard — Amazon Mini',
            'metrics' => $metrics,
            'recentOrders' => $recentOrders,
            'couriers' => $couriers,
            'revenueSeries' => Order::revenueByDay(7),
            'statusBreakdown' => Order::statusBreakdown(),
            'lowStock' => Product::lowStock(),
            'failedShipments' => Shipment::all(['status' => 'delivery_failed']),
        ]);
    }

    public function products(): void
    {
        $products = Product::search(['include_inactive' => true, 'limit' => 100]);
        $categories = Category::all();

        $editId = !empty($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
        $editProduct = $editId ? Product::findById($editId) : null;

        View::render('admin/products', [
            'pageTitle' => 'Manage Products — Amazon Mini Admin',
            'products' => $products,
            'categories' => $categories,
            'editProduct' => $editProduct,
        ]);
    }

    public function productSave(): void
    {
        $id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;

        $data = [
            'category_id' => (int)($_POST['category_id'] ?? 1),
            'sku' => trim($_POST['sku'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price' => (float)($_POST['price'] ?? 0.0),
            'stock' => (int)($_POST['stock'] ?? 0),
            'image_url' => trim($_POST['image_url'] ?? ''),
            'specs_json' => trim($_POST['specs_json'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if (empty($data['sku']) || empty($data['name']) || $data['price'] <= 0) {
            $_SESSION['flash_error'] = 'Please provide a valid SKU, product name, and price.';
            header('Location: ' . url('admin/products'));
            exit;
        }

        if ($id) {
            Product::update($id, $data);
            $_SESSION['flash_success'] = 'Product "' . htmlspecialchars($data['name']) . '" updated.';
        } else {
            Product::create($data);
            $_SESSION['flash_success'] = 'New product "' . htmlspecialchars($data['name']) . '" created.';
        }

        header('Location: ' . url('admin/products'));
        exit;
    }

    public function productDelete(): void
    {
        $id = (int)($_POST['product_id'] ?? 0);
        if ($id > 0) {
            Product::delete($id);
            $_SESSION['flash_success'] = 'Product deleted successfully.';
        }

        header('Location: ' . url('admin/products'));
        exit;
    }

    public function orders(): void
    {
        $statusFilter = $_GET['status'] ?? null;
        $orders = Order::all(['status' => $statusFilter]);
        $couriers = User::getCouriers();

        View::render('admin/orders', [
            'pageTitle' => 'Customer Orders & Shipments — Amazon Mini Admin',
            'orders' => $orders,
            'statusFilter' => $statusFilter,
            'couriers' => $couriers,
        ]);
    }

    public function packOrder(): void
    {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $courierId = !empty($_POST['courier_id']) ? (int)$_POST['courier_id'] : null;

        $order = Order::findById($orderId);
        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found.';
            header('Location: ' . url('admin/orders'));
            exit;
        }

        if ($order['payment_status'] !== 'paid' || in_array($order['order_status'], ['cancelled', 'returned'], true)) {
            $_SESSION['flash_error'] = 'Only paid, active orders can be packed.';
            header('Location: ' . url('admin/orders'));
            exit;
        }

        $shipment = Shipment::createForOrder($orderId, $courierId);

        $_SESSION['flash_success'] = 'Order #' . $order['order_number'] . ' packed! Tracking number: ' . $shipment['tracking_number'];
        header('Location: ' . url('admin/orders'));
        exit;
    }

    public function webhooks(): void
    {
        $events = Database::fetchAll('SELECT id, event_id, event_type, order_id, source, outcome, created_at FROM stripe_events ORDER BY id DESC LIMIT 25');
        $orders = Database::fetchAll('SELECT id, order_number, payment_status, order_status, total_amount FROM orders ORDER BY id DESC LIMIT 15');
        $config = require dirname(__DIR__, 2) . '/config/env.php';

        View::render('admin/webhooks', [
            'pageTitle' => 'Stripe Webhook Console — Amazon Mini Admin',
            'events' => $events,
            'orders' => $orders,
            'eventTypes' => StripeWebhookService::SUPPORTED_EVENTS,
            'appUrl' => rtrim($config['app_url'], '/'),
        ]);
    }

    /** Fires a locally generated Stripe event through the same handler the real endpoint uses. */
    public function webhookSimulate(): void
    {
        $type = trim($_POST['event_type'] ?? '');
        $order = Order::findById((int)($_POST['order_id'] ?? 0));

        if (!$order || !in_array($type, StripeWebhookService::SUPPORTED_EVENTS, true)) {
            $_SESSION['flash_error'] = 'Choose a valid order and event type.';
            header('Location: ' . url('admin/webhooks'));
            exit;
        }

        $config = require dirname(__DIR__, 2) . '/config/env.php';
        $event = StripeWebhookService::buildSimulatedEvent($type, $order);
        $result = (new StripeWebhookService($config))->handle($event, 'simulated');

        $_SESSION[$result['http'] < 400 ? 'flash_success' : 'flash_error'] = $type . ' → ' . $result['outcome'];
        header('Location: ' . url('admin/webhooks'));
        exit;
    }
}
