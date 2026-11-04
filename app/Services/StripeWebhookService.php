<?php
// app/Services/StripeWebhookService.php
// Processes Stripe's asynchronous event notifications (idempotent, audited).

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Order;

class StripeWebhookService
{
    public const SUPPORTED_EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
        'checkout.session.async_payment_failed',
        'checkout.session.expired',
        'payment_intent.payment_failed',
        'charge.refunded',
    ];

    private OrderService $orders;

    public function __construct(private array $config)
    {
        $this->orders = new OrderService($config);
    }

    /**
     * @return array{http:int,outcome:string,duplicate?:bool}
     */
    public function handle(array $event, string $source = 'stripe'): array
    {
        $eventId = (string)($event['id'] ?? '');
        $type = (string)($event['type'] ?? '');
        if ($eventId === '' || $type === '') {
            return ['http' => 400, 'outcome' => 'Malformed event payload'];
        }

        $object = $event['data']['object'] ?? [];
        $orderId = $this->resolveOrderId($object);

        // Idempotency: the unique key on event_id makes a retried delivery a no-op.
        try {
            Database::execute(
                'INSERT INTO stripe_events (event_id, event_type, order_id, source, outcome, payload) VALUES (?, ?, ?, ?, "processing", ?)',
                [$eventId, $type, $orderId, $source, json_encode($event, JSON_UNESCAPED_SLASHES)]
            );
        } catch (\PDOException $e) {
            if ((string)$e->getCode() !== '23000') {
                throw $e;
            }
            $previous = Database::fetchOne('SELECT outcome FROM stripe_events WHERE event_id = ?', [$eventId]);
            if (!$previous || !str_starts_with((string)$previous['outcome'], 'error')) {
                return ['http' => 200, 'outcome' => 'Duplicate event ignored', 'duplicate' => true];
            }
            // A previous attempt crashed: let Stripe's retry run the handler again.
            $this->finish($eventId, 'processing');
        }

        try {
            $outcome = $this->dispatch($type, $object, $orderId);
        } catch (\Throwable $e) {
            $this->finish($eventId, 'error: ' . $e->getMessage());
            return ['http' => 500, 'outcome' => 'Handler error: ' . $e->getMessage()];
        }

        $this->finish($eventId, $outcome);
        return ['http' => 200, 'outcome' => $outcome];
    }

    private function dispatch(string $type, array $object, ?int $orderId): string
    {
        if (!in_array($type, self::SUPPORTED_EVENTS, true)) {
            return 'ignored (unsupported event type)';
        }
        if ($orderId === null) {
            return 'ignored (no matching order)';
        }

        $order = Order::findById($orderId);
        if (!$order) {
            return 'ignored (order not found)';
        }

        switch ($type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                if (($object['payment_status'] ?? 'paid') !== 'paid') {
                    return 'waiting for async payment';
                }
                $result = $this->orders->settlePayment(
                    $orderId,
                    (string)($object['id'] ?? ($order['stripe_session_id'] ?? 'cs_webhook_' . $orderId)),
                    isset($object['payment_intent']) ? (string)$object['payment_intent'] : null
                );
                return $result['status'] . ': ' . $result['message'];

            case 'checkout.session.async_payment_failed':
            case 'payment_intent.payment_failed':
                return Order::markPaymentFailed($orderId) ? 'payment marked as failed' : 'no change (order not pending)';

            case 'checkout.session.expired':
                if ($order['payment_status'] === 'pending' && $order['order_status'] === 'placed') {
                    Order::markCancelled($orderId, 'Checkout session expired', 'pending');
                    return 'pending order cancelled (session expired)';
                }
                return 'no change (order already processed)';

            case 'charge.refunded':
                if ($order['payment_status'] === 'refunded') {
                    return 'no change (already refunded)';
                }
                if ($order['payment_status'] === 'paid') {
                    if (!in_array($order['order_status'], ['cancelled', 'returned'], true)) {
                        Order::restockItems($orderId, 'stripe_refund');
                        \App\Models\Shipment::cancelForOrder($orderId, 'Payment refunded via Stripe');
                    }
                    Order::markCancelled($orderId, 'Refunded via Stripe dashboard', 'refunded', (string)($object['id'] ?? null));
                    return 'order refunded and cancelled';
                }
                return 'no change';
        }

        return 'ignored';
    }

    private function resolveOrderId(array $object): ?int
    {
        foreach ([$object['metadata']['order_id'] ?? null, $object['client_reference_id'] ?? null] as $candidate) {
            if (is_numeric($candidate)) {
                return (int)$candidate;
            }
        }
        if (!empty($object['id']) && is_string($object['id'])) {
            $order = Order::findByStripeSession($object['id']);
            if ($order) {
                return (int)$order['id'];
            }
        }
        if (!empty($object['payment_intent']) && is_string($object['payment_intent'])) {
            $row = Database::fetchOne('SELECT id FROM orders WHERE stripe_payment_intent = ? LIMIT 1', [$object['payment_intent']]);
            if ($row) {
                return (int)$row['id'];
            }
        }
        if (!empty($object['id']) && str_starts_with((string)$object['id'], 'pi_')) {
            $row = Database::fetchOne('SELECT id FROM orders WHERE stripe_payment_intent = ? LIMIT 1', [$object['id']]);
            if ($row) {
                return (int)$row['id'];
            }
        }
        return null;
    }

    private function finish(string $eventId, string $outcome): void
    {
        Database::execute('UPDATE stripe_events SET outcome = ? WHERE event_id = ?', [mb_substr($outcome, 0, 255), $eventId]);
    }

    /** Builds a realistic Stripe event for the admin simulator. */
    public static function buildSimulatedEvent(string $type, array $order): array
    {
        $sessionId = $order['stripe_session_id'] ?: 'cs_sim_' . bin2hex(random_bytes(6));
        $intent = $order['stripe_payment_intent'] ?: 'pi_sim_' . bin2hex(random_bytes(6));

        $object = match (true) {
            str_starts_with($type, 'checkout.session') => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'client_reference_id' => (string)$order['id'],
                'payment_status' => $type === 'checkout.session.completed' || $type === 'checkout.session.async_payment_succeeded' ? 'paid' : 'unpaid',
                'payment_intent' => $intent,
                'amount_total' => (int)round((float)$order['total_amount'] * 100),
                'metadata' => ['order_id' => (string)$order['id'], 'order_number' => $order['order_number']],
            ],
            $type === 'charge.refunded' => [
                'id' => 'ch_sim_' . bin2hex(random_bytes(5)),
                'object' => 'charge',
                'payment_intent' => $intent,
                'metadata' => ['order_id' => (string)$order['id']],
                'refunded' => true,
            ],
            default => [
                'id' => $intent,
                'object' => 'payment_intent',
                'metadata' => ['order_id' => (string)$order['id']],
                'last_payment_error' => ['message' => 'Your card was declined.'],
            ],
        };

        return [
            'id' => 'evt_sim_' . bin2hex(random_bytes(8)),
            'object' => 'event',
            'api_version' => '2018-05-21',
            'created' => time(),
            'type' => $type,
            'livemode' => false,
            'data' => ['object' => $object],
        ];
    }
}
