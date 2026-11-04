<?php
// app/Models/Shipment.php
// Logistics state machine, tracking number generation, and timeline event audit trail.

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Shipment
{
    public const STATUSES = [
        'awaiting_pickup' => [
            'label' => 'Packed & Awaiting Pickup',
            'step' => 1,
            'description' => 'Order has been packed in warehouse and shipping label generated.'
        ],
        'picked_up' => [
            'label' => 'Picked Up',
            'step' => 2,
            'description' => 'Carrier received package from fulfillment facility.'
        ],
        'in_transit' => [
            'label' => 'In Transit',
            'step' => 3,
            'description' => 'Package is moving between regional sorting hubs.'
        ],
        'out_for_delivery' => [
            'label' => 'Out for Delivery',
            'step' => 4,
            'description' => 'Courier is currently delivering package to recipient address.'
        ],
        'delivered' => [
            'label' => 'Delivered',
            'step' => 5,
            'description' => 'Package has been delivered to customer.'
        ],
    ];

    public const EXCEPTION_STATUSES = [
        'delivery_failed' => [
            'label' => 'Delivery Failed',
            'step' => 0,
            'description' => 'Courier could not hand over the package. A new attempt will be scheduled.'
        ],
        'returned_to_sender' => [
            'label' => 'Returned to Sender',
            'step' => 0,
            'description' => 'Maximum delivery attempts reached. Package is returning to the warehouse.'
        ],
        'cancelled' => [
            'label' => 'Shipment Cancelled',
            'step' => 0,
            'description' => 'Order was cancelled before the package reached the customer.'
        ],
    ];

    public const FAILURE_REASONS = [
        'RECIPIENT_ABSENT' => 'Recipient not at address (nobody home)',
        'ADDRESS_NOT_FOUND' => 'Address not found / incorrect address',
        'RECIPIENT_REFUSED' => 'Recipient refused the package',
        'BUSINESS_CLOSED' => 'Business closed / no one to sign',
        'ACCESS_DENIED' => 'No access to building (gate / security)',
    ];

    public const MAX_DELIVERY_ATTEMPTS = 3;

    private const TRANSITIONS = [
        'awaiting_pickup' => ['picked_up'],
        'picked_up' => ['in_transit'],
        'in_transit' => ['out_for_delivery'],
        'out_for_delivery' => ['delivered', 'delivery_failed'],
        'delivery_failed' => ['out_for_delivery', 'returned_to_sender'],
        'delivered' => [],
        'returned_to_sender' => [],
        'cancelled' => [],
    ];

    public static function allStatuses(): array
    {
        return self::STATUSES + self::EXCEPTION_STATUSES;
    }

    public static function allowedNext(string $current): array
    {
        return self::TRANSITIONS[$current] ?? [];
    }

    public static function createForOrder(int $orderId, ?int $courierId = null): ?array
    {
        $existing = self::findByOrderId($orderId);
        if ($existing) {
            return $existing;
        }

        $trackingNumber = self::generateTrackingNumber();
        $estimatedDate = date('Y-m-d', strtotime('+3 days'));

        Database::beginTransaction();
        try {
            Database::execute(
                'INSERT INTO shipments (order_id, tracking_number, carrier_name, assigned_courier_id, current_status, estimated_delivery)
                 VALUES (?, ?, "Amazon Logistics Express", ?, "awaiting_pickup", ?)',
                [$orderId, $trackingNumber, $courierId, $estimatedDate]
            );

            $shipmentId = (int)Database::lastInsertId();

            // Record initial timeline event
            Database::execute(
                'INSERT INTO shipment_events (shipment_id, status_step, location, notes, updated_by_user_id)
                 VALUES (?, "awaiting_pickup", "Amazon Fulfillment Center #12, Seattle WA", "Shipping label created and package packed ready for courier pickup.", ?)',
                [$shipmentId, $courierId]
            );

            // Update order status to 'packed'
            Database::execute('UPDATE orders SET order_status = "packed" WHERE id = ?', [$orderId]);

            Database::commit();
            return self::findByTrackingNumber($trackingNumber);
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function findByOrderId(int $orderId): ?array
    {
        return Database::fetchOne(
            'SELECT s.*, u.name as courier_name, u.phone as courier_phone
             FROM shipments s
             LEFT JOIN users u ON s.assigned_courier_id = u.id
             WHERE s.order_id = ? LIMIT 1',
            [$orderId]
        );
    }

    public static function findByTrackingNumber(string $trackingNumber): ?array
    {
        $shipment = Database::fetchOne(
            'SELECT s.*, o.order_number, o.user_id as customer_id, o.address_snapshot_json,
                    u.name as courier_name, u.phone as courier_phone, c.name as customer_name
             FROM shipments s
             JOIN orders o ON s.order_id = o.id
             JOIN users c ON o.user_id = c.id
             LEFT JOIN users u ON s.assigned_courier_id = u.id
             WHERE s.tracking_number = ? LIMIT 1',
            [trim($trackingNumber)]
        );

        if ($shipment) {
            $shipment['address_snapshot'] = json_decode($shipment['address_snapshot_json'], true);
            $shipment['events'] = self::getEvents((int)$shipment['id']);
        }

        return $shipment;
    }

    public static function getEvents(int $shipmentId): array
    {
        return Database::fetchAll(
            'SELECT e.*, u.name as updater_name, u.role as updater_role
             FROM shipment_events e
             LEFT JOIN users u ON e.updated_by_user_id = u.id
             WHERE e.shipment_id = ?
             ORDER BY e.event_time DESC, e.id DESC',
            [$shipmentId]
        );
    }

    public static function updateStatus(int $shipmentId, string $newStatus, string $location, string $notes, ?int $userId = null, ?string $reasonCode = null): bool
    {
        if (!isset(self::allStatuses()[$newStatus]) || $newStatus === 'cancelled') {
            return false;
        }

        $current = Database::fetchOne('SELECT order_id, current_status, delivery_attempts FROM shipments WHERE id = ?', [$shipmentId]);
        if (!$current) {
            return false;
        }

        if (!in_array($newStatus, self::allowedNext($current['current_status']), true)) {
            throw new \InvalidArgumentException(sprintf(
                'Illegal transition: %s -> %s',
                $current['current_status'],
                $newStatus
            ));
        }

        if ($newStatus === 'delivery_failed') {
            if (!isset(self::FAILURE_REASONS[$reasonCode ?? ''])) {
                throw new \InvalidArgumentException('A valid failure reason is required for a failed delivery.');
            }
        } else {
            $reasonCode = null;
        }

        Database::beginTransaction();
        try {
            $attempts = (int)$current['delivery_attempts'];
            $finalStatus = $newStatus;
            $returnNote = null;

            if ($newStatus === 'delivery_failed') {
                $attempts++;
                $notes = sprintf('Attempt %d of %d: %s. %s', $attempts, self::MAX_DELIVERY_ATTEMPTS, self::FAILURE_REASONS[$reasonCode], trim($notes));
                if ($attempts >= self::MAX_DELIVERY_ATTEMPTS) {
                    $finalStatus = 'returned_to_sender';
                    $returnNote = 'Maximum delivery attempts reached. Package is being returned to the fulfillment center.';
                }
            }

            self::recordEvent($shipmentId, $newStatus, $location, $notes, $userId, $reasonCode);
            if ($returnNote !== null) {
                self::recordEvent($shipmentId, 'returned_to_sender', 'Amazon Fulfillment Center #12, Seattle WA', $returnNote, $userId, null);
            }

            if ($newStatus === 'delivery_failed' && $finalStatus === 'delivery_failed') {
                Database::execute(
                    'UPDATE shipments SET current_status = ?, delivery_attempts = ?, estimated_delivery = DATE_ADD(CURDATE(), INTERVAL 1 DAY) WHERE id = ?',
                    [$finalStatus, $attempts, $shipmentId]
                );
            } else {
                Database::execute(
                    'UPDATE shipments SET current_status = ?, delivery_attempts = ? WHERE id = ?',
                    [$finalStatus, $attempts, $shipmentId]
                );
            }

            $orderStatus = match ($finalStatus) {
                'delivered' => 'delivered',
                'returned_to_sender' => 'returned',
                default => 'shipped',
            };
            Database::execute('UPDATE orders SET order_status = ? WHERE id = ?', [$orderStatus, $current['order_id']]);

            Database::commit();
            return true;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    private static function recordEvent(int $shipmentId, string $status, string $location, string $notes, ?int $userId, ?string $reasonCode): void
    {
        Database::execute(
            'INSERT INTO shipment_events (shipment_id, status_step, location, reason_code, notes, updated_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$shipmentId, $status, trim($location), $reasonCode, trim($notes), $userId]
        );
    }

    /** Marks an in-flight shipment as cancelled (audit trail is preserved). */
    public static function cancelForOrder(int $orderId, string $reason, ?int $userId = null): void
    {
        $shipment = self::findByOrderId($orderId);
        if (!$shipment || in_array($shipment['current_status'], ['delivered', 'cancelled', 'returned_to_sender'], true)) {
            return;
        }
        Database::execute('UPDATE shipments SET current_status = "cancelled" WHERE id = ?', [$shipment['id']]);
        self::recordEvent((int)$shipment['id'], 'cancelled', 'Amazon Fulfillment Center #12, Seattle WA', $reason, $userId, null);
    }

    public static function all(array $filters = []): array
    {
        $sql = 'SELECT s.*, o.order_number, o.created_at as order_date,
                       c.name as customer_name, u.name as courier_name
                FROM shipments s
                JOIN orders o ON s.order_id = o.id
                JOIN users c ON o.user_id = c.id
                LEFT JOIN users u ON s.assigned_courier_id = u.id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['courier_id'])) {
            $sql .= ' AND (s.assigned_courier_id = ? OR s.assigned_courier_id IS NULL)';
            $params[] = (int)$filters['courier_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND s.current_status = ?';
            $params[] = $filters['status'];
        }

        $sql .= ' ORDER BY s.id DESC';
        return Database::fetchAll($sql, $params);
    }

    public static function generateTrackingNumber(): string
    {
        // Format: TRK-2026-XXXXX (e.g., TRK-2026-89412)
        $num = str_pad((string)random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        return 'TRK-' . date('Y') . '-' . $num;
    }
}
