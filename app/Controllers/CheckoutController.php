<?php
// app/Controllers/CheckoutController.php
// Stripe Checkout Session creation and return verification.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\OrderService;
use App\Services\StripeService;

class CheckoutController
{
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/env.php';
    }

    public function index(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $summary = Cart::getSummary(Auth::id());
        if ($summary['count'] === 0) {
            $_SESSION['flash_error'] = 'Your shopping cart is currently empty.';
            header('Location: ' . url('cart'));
            exit;
        }

        $addresses = Address::getByUserId(Auth::id());
        $defaultAddress = Address::getDefault(Auth::id());

        $stripeService = new StripeService($this->config);
        $stripeReady = $stripeService->isConfigured();

        View::render('checkout/index', [
            'pageTitle' => 'Checkout — Amazon Mini',
            'summary' => $summary,
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'stripeReady' => $stripeReady,
            'demoPayments' => !$stripeReady && !empty($this->config['demo_payments']),
            'stripePublishableKey' => $stripeService->getPublishableKey(),
        ]);
    }

    public function createSession(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $summary = Cart::getSummary(Auth::id());
        if ($summary['count'] === 0) {
            $_SESSION['flash_error'] = 'Your cart is empty.';
            header('Location: ' . url('cart'));
            exit;
        }

        $addressId = (int)($_POST['address_id'] ?? 0);
        $address = Address::findByIdAndUser($addressId, Auth::id());
        if (!$address) {
            $_SESSION['flash_error'] = 'Please select a valid shipping address.';
            header('Location: ' . url('checkout'));
            exit;
        }

        // Verify stock for all cart items before proceeding
        foreach ($summary['items'] as $item) {
            if ($item['stock'] < $item['quantity']) {
                $_SESSION['flash_error'] = "Insufficient stock for '{$item['name']}'. Only {$item['stock']} available.";
                header('Location: ' . url('cart'));
                exit;
            }
        }

        // Create initial pending order
        $orderId = Order::createFromCart(Auth::id(), $address, $summary);
        $order = Order::findById($orderId);
        $orderItems = Order::getItems($orderId);

        $appUrl = rtrim($this->config['app_url'], '/');
        // Ensure success/cancel URLs use absolute URLs for Stripe
        $successUrl = $appUrl . '/index.php?route=checkout/success&session_id={CHECKOUT_SESSION_ID}&order_id=' . $orderId;
        $cancelUrl = $appUrl . '/index.php?route=checkout/cancel&order_id=' . $orderId;

        $stripeService = new StripeService($this->config);

        if (!$stripeService->isConfigured() && !empty($this->config['demo_payments'])) {
            $this->finishPayment($orderId, 'cs_demo_' . bin2hex(random_bytes(8)), 'pi_demo_' . bin2hex(random_bytes(8)));
        }

        try {
            $session = $stripeService->createCheckoutSession($order, $orderItems, $successUrl, $cancelUrl);
            Order::attachSession($orderId, $session['id']);
            header('Location: ' . $session['url']);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Payment initialization error: ' . $e->getMessage();
            header('Location: ' . url('checkout'));
            exit;
        }
    }

    public function success(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $sessionId = $_GET['session_id'] ?? '';
        $orderId = (int)($_GET['order_id'] ?? 0);

        if (empty($sessionId) || $orderId <= 0) {
            $_SESSION['flash_error'] = 'Invalid checkout session callback.';
            header('Location: ' . url('home'));
            exit;
        }

        $order = Order::findById($orderId);
        if (!$order || (int)$order['user_id'] !== Auth::id()) {
            http_response_code(403);
            View::render('home/error', ['statusCode' => 403, 'title' => 'Access Denied', 'message' => 'Unauthorized order lookup.']);
            return;
        }

        $stripeService = new StripeService($this->config);

        try {
            $stripeSession = $stripeService->retrieveCheckoutSession($sessionId);

            if (($stripeSession['payment_status'] ?? '') === 'paid') {
                $paymentIntent = (string)($stripeSession['payment_intent'] ?? '');
                $this->finishPayment($orderId, $sessionId, $paymentIntent);
            } else {
                $_SESSION['flash_error'] = 'Payment has not been confirmed by Stripe yet. Please contact support if your card was charged.';
                header('Location: ' . url('order/view', ['id' => $orderId]));
                exit;
            }
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Error verifying payment with Stripe: ' . $e->getMessage();
            header('Location: ' . url('order/view', ['id' => $orderId]));
            exit;
        }
    }

    /** Settles the payment and redirects; handles the "sold out while paying" race. */
    private function finishPayment(int $orderId, string $sessionId, string $paymentIntent): never
    {
        try {
            $result = (new OrderService($this->config))->settlePayment($orderId, $sessionId, $paymentIntent ?: null);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Error finalising your order: ' . $e->getMessage();
            header('Location: ' . url('order/view', ['id' => $orderId]));
            exit;
        }

        if (in_array($result['status'], ['paid', 'already_paid'], true)) {
            $_SESSION['flash_success'] = 'Payment completed successfully! Your order has been placed.';
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        header('Location: ' . url('order/view', ['id' => $orderId]));
        exit;
    }

    public function cancel(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $orderId = (int)($_GET['order_id'] ?? 0);
        $order = $orderId > 0 ? Order::findById($orderId) : null;
        if ($order && (int)$order['user_id'] === Auth::id() && $order['payment_status'] === 'pending' && $order['order_status'] === 'placed') {
            (new OrderService($this->config))->cancelOrder($orderId, 'Payment abandoned at checkout', Auth::id());
        }
        $_SESSION['flash_error'] = 'Payment was cancelled. You can review your items and try again when you are ready.';
        header('Location: ' . url('checkout'));
        exit;
    }
}
