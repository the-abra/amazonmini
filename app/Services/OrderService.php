<?php
// app/Services/OrderService.php
// Business workflows that span several models: payment settlement, cancellation and returns.
// Shared by the browser checkout callback, the Stripe webhook and the admin/courier portals.

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\OutOfStockException;
use App\Core\Telemetry;
use App\Models\Order;
use App\Models\Shipment;

class OrderService
{
    private StripeService $stripe;

    public function __construct(array $config)
    {
        $this->stripe = new StripeService($config);
    }

    /**
     * Idempotently settles a payment. Safe to call from both the success redirect and the webhook.
     *
     * @return array{status:string,message:string,items?:array}
     */
    public function settlePayment(int $orderId, string $sessionId, ?string $paymentIntent = null): array
    {
        $order = Order::findById($orderId);
        if (!$order) {
            return ['status' => 'not_found', 'message' => 'Order not found.'];
        }

        if (in_array($order['order_status'], ['cancelled', 'returned'], true)) {
            if ($order['payment_status'] === 'refunded') {
                return ['status' => 'cancelled', 'message' => 'Order was already cancelled and refunded.'];
            }
            // Money arrived for an order we already cancelled (e.g. expired session): give it back.
            $refundId = $this->stripe->refund($paymentIntent ?: ($order['stripe_payment_intent'] ?? null));
            Database::execute(
                'UPDATE orders SET payment_status = "refunded", refund_id = ?, stripe_payment_intent = COALESCE(?, stripe_payment_intent) WHERE id = ?',
                [$refundId, $paymentIntent, $orderId]
            );
            return ['status' => 'cancelled', 'message' => 'Late payment for a cancelled order was refunded automatically.'];
        }

        if ($order['payment_status'] === 'paid') {
            return ['status' => 'already_paid', 'message' => 'Payment was already recorded.'];
        }

        try {
            Order::markPaid($orderId, $sessionId, $paymentIntent);
        } catch (OutOfStockException $e) {
            return $this->failForStock($orderId, $sessionId, $paymentIntent, $e);
        }

        Shipment::createForOrder($orderId);
        Telemetry::recordEvent('order_paid', ['order_id' => $orderId]);
        return ['status' => 'paid', 'message' => 'Payment confirmed.'];
    }

    private function failForStock(int $orderId, string $sessionId, ?string $paymentIntent, OutOfStockException $e): array
    {
        Order::attachSession($orderId, $sessionId);
        if ($paymentIntent) {
            Database::execute('UPDATE orders SET stripe_payment_intent = ? WHERE id = ?', [$paymentIntent, $orderId]);
        }

        $reason = 'Out of stock at payment time: ' . implode(', ', $e->getProductNames());
        try {
            $refundId = $this->stripe->refund($paymentIntent);
            Order::markCancelled($orderId, $reason, 'refunded', $refundId);
            $message = 'Sorry, an item sold out while you were paying. Your order was cancelled and your payment has been refunded in full.';
        } catch (\Throwable $refundError) {
            Order::markCancelled($orderId, $reason . ' (automatic refund failed, manual refund required)', 'paid');
            $message = 'Sorry, an item sold out while you were paying. Our team will refund your payment manually.';
        }

        Telemetry::recordEvent('order_stock_conflict', ['order_id' => $orderId, 'items' => $e->getProductNames()]);
        return ['status' => 'out_of_stock', 'message' => $message, 'items' => $e->getProductNames()];
    }

    /**
     * Cancels an order that has not left the warehouse.
     *
     * @return array{ok:bool,message:string}
     */
    public function cancelOrder(int $orderId, string $reason, ?int $actorId = null): array
    {
        $order = Order::findById($orderId);
        if (!$order) {
            return ['ok' => false, 'message' => 'Order not found.'];
        }
        if (!Order::canCustomerCancel($order)) {
            return ['ok' => false, 'message' => 'This order can no longer be cancelled because it has already been shipped.'];
        }

        $wasPaid = $order['payment_status'] === 'paid';
        $refundId = null;
        if ($wasPaid) {
            try {
                $refundId = $this->stripe->refund($order['stripe_payment_intent'] ?? null);
            } catch (\Throwable $e) {
                return ['ok' => false, 'message' => 'Refund could not be issued: ' . $e->getMessage()];
            }
        }

        Database::beginTransaction();
        try {
            $locked = Database::fetchOne('SELECT order_status FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if (!$locked || in_array($locked['order_status'], ['cancelled', 'returned'], true)) {
                Database::rollBack();
                return ['ok' => true, 'message' => 'Order was already cancelled.'];
            }
            if ($wasPaid) {
                Order::restockItems($orderId, 'order_cancelled');
            }
            Shipment::cancelForOrder($orderId, $reason, $actorId);
            Order::markCancelled($orderId, $reason, $wasPaid ? 'refunded' : $order['payment_status'], $refundId);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }

        Telemetry::recordEvent('order_cancelled', ['order_id' => $orderId, 'refunded' => $wasPaid]);
        return [
            'ok' => true,
            'message' => $wasPaid
                ? 'Order cancelled. Your payment will be refunded to the original card.'
                : 'Order cancelled.',
        ];
    }

    /** Called once a shipment reaches "returned_to_sender": restock and refund the customer. */
    public function processReturn(int $orderId): void
    {
        $order = Order::findById($orderId);
        if (!$order || $order['payment_status'] === 'refunded') {
            return;
        }

        $refundId = null;
        $paymentStatus = $order['payment_status'];
        if ($order['payment_status'] === 'paid') {
            try {
                $refundId = $this->stripe->refund($order['stripe_payment_intent'] ?? null);
                $paymentStatus = 'refunded';
            } catch (\Throwable $e) {
                error_log('[OrderService] Refund failed for returned order ' . $orderId . ': ' . $e->getMessage());
            }
            Order::restockItems($orderId, 'returned_to_sender');
        }
        Order::markReturned($orderId, $paymentStatus, $refundId);
    }
}
