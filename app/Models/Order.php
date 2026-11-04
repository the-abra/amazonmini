<?php
// app/Models/Order.php
// Order lifecycle and items persistence.

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\OutOfStockException;

class Order
{
    public static function createFromCart(int $userId, array $address, array $cartSummary): int
    {
        Database::beginTransaction();
        try {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $addressJson = json_encode($address, JSON_UNESCAPED_UNICODE);

            Database::execute(
                'INSERT INTO orders (order_number, user_id, address_snapshot_json, subtotal, shipping_cost, total_amount, payment_status, order_status)
                 VALUES (?, ?, ?, ?, ?, ?, "pending", "placed")',
                [
                    $orderNumber,
                    $userId,
                    $addressJson,
                    $cartSummary['subtotal'],
                    $cartSummary['shipping'],
                    $cartSummary['total'],
                ]
            );

            $orderId = (int)Database::lastInsertId();

            foreach ($cartSummary['items'] as $item) {
                Database::execute(
                    'INSERT INTO order_items (order_id, product_id, product_name, sku, unit_price, quantity, line_total)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $orderId,
                        $item['id'],
                        $item['name'],
                        $item['sku'],
                        $item['price'],
                        $item['quantity'],
                        $item['line_total'],
                    ]
                );
            }

            Database::commit();
            return $orderId;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function findById(int $id): ?array
    {
        $order = Database::fetchOne(
            'SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = ? LIMIT 1',
            [$id]
        );
        if ($order) {
            $order['address_snapshot'] = json_decode($order['address_snapshot_json'], true);
        }
        return $order;
    }

    public static function findByOrderNumber(string $orderNumber): ?array
    {
        $order = Database::fetchOne(
            'SELECT o.*, u.name as customer_name, u.email as customer_email 
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.order_number = ? LIMIT 1',
            [trim($orderNumber)]
        );
        if ($order) {
            $order['address_snapshot'] = json_decode($order['address_snapshot_json'], true);
        }
        return $order;
    }

    public static function getByUserId(int $userId): array
    {
        $orders = Database::fetchAll(
            'SELECT o.*, s.tracking_number, s.current_status as shipment_status
             FROM orders o
             LEFT JOIN shipments s ON o.id = s.order_id
             WHERE o.user_id = ?
             ORDER BY o.id DESC',
            [$userId]
        );

        foreach ($orders as &$order) {
            $order['address_snapshot'] = json_decode($order['address_snapshot_json'], true);
        }
        return $orders;
    }

    public static function all(array $filters = []): array
    {
        $sql = 'SELECT o.*, u.name as customer_name, u.email as customer_email,
                       s.tracking_number, s.current_status as shipment_status
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN shipments s ON o.id = s.order_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND o.order_status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $sql .= ' AND o.payment_status = ?';
            $params[] = $filters['payment_status'];
        }

        $sql .= ' ORDER BY o.id DESC';
        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . (int)$filters['limit'];
        }
        $orders = Database::fetchAll($sql, $params);

        foreach ($orders as &$order) {
            $order['address_snapshot'] = json_decode($order['address_snapshot_json'], true);
        }
        return $orders;
    }

    public static function getItems(int $orderId): array
    {
        return Database::fetchAll(
            'SELECT oi.*, p.image_url, p.slug as product_slug
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC',
            [$orderId]
        );
    }

    /**
     * Settles a pending order: deducts stock atomically and clears the cart.
     * Throws OutOfStockException (after rolling back) when any line cannot be fulfilled.
     */
    public static function markPaid(int $orderId, string $sessionId, ?string $paymentIntent = null): bool
    {
        Database::beginTransaction();
        try {
            // Lock the order row to prevent race conditions (double settlement)
            $order = Database::fetchOne(
                'SELECT * FROM orders WHERE id = ? FOR UPDATE',
                [$orderId]
            );
            
            if (!$order) {
                Database::rollBack();
                return false;
            }

            if ($order['payment_status'] === 'paid') {
                Database::rollBack();
                return true;
            }

            $missing = [];
            foreach (self::getItems($orderId) as $item) {
                if (!Product::decrementStock((int)$item['product_id'], (int)$item['quantity'], $orderId)) {
                    $missing[] = $item['product_name'];
                }
            }

            if ($missing) {
                Database::rollBack();
                throw new OutOfStockException($missing);
            }

            Database::execute(
                'UPDATE orders SET payment_status = "paid", stripe_session_id = ?, stripe_payment_intent = ? WHERE id = ?',
                [$sessionId, $paymentIntent, $orderId]
            );

            Cart::clear((int)$order['user_id']);

            Database::commit();
            return true;
        } catch (OutOfStockException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function findByStripeSession(string $sessionId): ?array
    {
        $row = Database::fetchOne('SELECT id FROM orders WHERE stripe_session_id = ? LIMIT 1', [$sessionId]);
        return $row ? self::findById((int)$row['id']) : null;
    }

    public static function attachSession(int $orderId, string $sessionId): void
    {
        Database::execute('UPDATE orders SET stripe_session_id = ? WHERE id = ?', [$sessionId, $orderId]);
    }

    public static function markPaymentFailed(int $orderId): bool
    {
        return Database::execute('UPDATE orders SET payment_status = "failed" WHERE id = ? AND payment_status = "pending"', [$orderId]) > 0;
    }

    public static function canCustomerCancel(array $order): bool
    {
        return in_array($order['order_status'], ['placed', 'packed'], true)
            && in_array($order['payment_status'], ['pending', 'paid', 'failed'], true);
    }

    /** Returns purchased units to inventory (only call for orders whose stock was deducted). */
    public static function restockItems(int $orderId, string $reason): void
    {
        foreach (self::getItems($orderId) as $item) {
            Product::restock((int)$item['product_id'], (int)$item['quantity'], $reason, $orderId);
        }
    }

    public static function markCancelled(int $orderId, string $reason, string $paymentStatus, ?string $refundId = null): bool
    {
        return Database::execute(
            'UPDATE orders SET order_status = "cancelled", payment_status = ?, cancel_reason = ?, cancelled_at = NOW(), refund_id = ? WHERE id = ?',
            [$paymentStatus, mb_substr($reason, 0, 255), $refundId, $orderId]
        ) > 0;
    }

    public static function markReturned(int $orderId, string $paymentStatus, ?string $refundId = null): bool
    {
        return Database::execute(
            'UPDATE orders SET order_status = "returned", payment_status = ?, refund_id = ?, cancelled_at = NOW(), cancel_reason = "Returned to sender after failed deliveries" WHERE id = ?',
            [$paymentStatus, $refundId, $orderId]
        ) > 0;
    }

    public static function updateStatus(int $orderId, string $status): bool
    {
        return Database::execute('UPDATE orders SET order_status = ? WHERE id = ?', [$status, $orderId]) > 0;
    }

    public static function getMetrics(): array
    {
        $sales = Database::fetchOne('SELECT SUM(total_amount) as total_revenue, COUNT(*) as total_orders FROM orders WHERE payment_status = "paid"');
        $prods = Database::fetchOne('SELECT COUNT(*) as total_products, SUM(stock) as total_stock FROM products WHERE is_active = 1');
        $custs = Database::fetchOne('SELECT COUNT(*) as total_customers FROM users WHERE role = "customer"');
        $shipments = Database::fetchOne('SELECT COUNT(*) as active_shipments FROM shipments WHERE current_status NOT IN ("delivered", "returned_to_sender", "cancelled")');
        $failed = Database::fetchOne('SELECT COUNT(*) as failed_deliveries FROM shipments WHERE current_status = "delivery_failed"');
        $cancelled = Database::fetchOne('SELECT COUNT(*) as cancelled_orders FROM orders WHERE order_status IN ("cancelled", "returned")');

        return [
            'total_revenue' => (float)($sales['total_revenue'] ?? 0.0),
            'total_orders' => (int)($sales['total_orders'] ?? 0),
            'total_products' => (int)($prods['total_products'] ?? 0),
            'total_stock' => (int)($prods['total_stock'] ?? 0),
            'total_customers' => (int)($custs['total_customers'] ?? 0),
            'active_shipments' => (int)($shipments['active_shipments'] ?? 0),
            'failed_deliveries' => (int)($failed['failed_deliveries'] ?? 0),
            'cancelled_orders' => (int)($cancelled['cancelled_orders'] ?? 0),
        ];
    }

    /** Paid revenue per day for the last N days (zero-filled) for the dashboard chart. */
    public static function revenueByDay(int $days = 7): array
    {
        $rows = Database::fetchAll(
            'SELECT DATE(created_at) AS d, SUM(total_amount) AS revenue, COUNT(*) AS orders
             FROM orders WHERE payment_status = "paid" AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)',
            [$days - 1]
        );
        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r['d']] = $r;
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'date' => $d,
                'label' => date('D', strtotime($d)),
                'revenue' => (float)($byDate[$d]['revenue'] ?? 0),
                'orders' => (int)($byDate[$d]['orders'] ?? 0),
            ];
        }
        return $series;
    }

    public static function statusBreakdown(): array
    {
        $rows = Database::fetchAll('SELECT order_status, COUNT(*) AS cnt FROM orders GROUP BY order_status');
        $out = [];
        foreach ($rows as $r) {
            $out[$r['order_status']] = (int)$r['cnt'];
        }
        return $out;
    }
}
