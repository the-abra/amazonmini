<?php
// app/Controllers/OrderController.php
// Customer order history and order invoice detail.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\OrderService;

class OrderController
{
    public function index(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $orders = Order::getByUserId(Auth::id());

        View::render('order/index', [
            'pageTitle' => 'Your Orders — Amazon Mini',
            'orders' => $orders,
        ]);
    }

    public function view(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $orderId = (int)($_GET['id'] ?? 0);
        $order = Order::findById($orderId);

        if (!$order) {
            http_response_code(404);
            View::render('home/error', ['statusCode' => 404, 'title' => 'Order Not Found', 'message' => 'The requested order does not exist.']);
            return;
        }

        // Authorization check: only owner, admin, or courier can view
        if ((int)$order['user_id'] !== Auth::id() && !Auth::hasRole('admin', 'courier')) {
            http_response_code(403);
            View::render('home/error', ['statusCode' => 403, 'title' => 'Access Denied', 'message' => 'You cannot view another user\'s order.']);
            return;
        }

        $items = Order::getItems($orderId);
        $shipment = Shipment::findByOrderId($orderId);

        View::render('order/view', [
            'pageTitle' => 'Order Details #' . $order['order_number'] . ' — Amazon Mini',
            'order' => $order,
            'items' => $items,
            'shipment' => $shipment,
            'canCancel' => Order::canCustomerCancel($order) && (int)$order['user_id'] === Auth::id(),
        ]);
    }

    public function cancel(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $orderId = (int)($_POST['order_id'] ?? 0);
        $order = Order::findById($orderId);

        if (!$order || ((int)$order['user_id'] !== Auth::id() && !Auth::hasRole('admin'))) {
            http_response_code(403);
            View::render('home/error', ['statusCode' => 403, 'title' => 'Access Denied', 'message' => 'You cannot cancel this order.']);
            return;
        }

        $config = require dirname(__DIR__, 2) . '/config/env.php';
        $reason = Auth::hasRole('admin') && (int)$order['user_id'] !== Auth::id() ? 'Cancelled by store admin' : 'Cancelled by customer';
        $result = (new OrderService($config))->cancelOrder($orderId, $reason, Auth::id());

        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        header('Location: ' . url('order/view', ['id' => $orderId]));
        exit;
    }
}
