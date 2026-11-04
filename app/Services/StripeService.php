<?php
// app/Services/StripeService.php
// Pure PHP cURL integration for Stripe Checkout Sessions Sandbox.

declare(strict_types=1);

namespace App\Services;

use App\Core\Telemetry;

class StripeService
{
    private string $secretKey;
    private string $publishableKey;
    private string $webhookSecret;
    private string $apiBase = 'https://api.stripe.com/v1';

    public function __construct(array $config)
    {
        $this->secretKey = (string)($config['stripe']['secret_key'] ?? '');
        $this->publishableKey = (string)($config['stripe']['publishable_key'] ?? '');
        $this->webhookSecret = (string)($config['stripe']['webhook_secret'] ?? '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey) && !str_contains($this->secretKey, 'placeholder');
    }

    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }

    public function createCheckoutSession(array $order, array $items, string $successUrl, string $cancelUrl): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException(
                'Stripe Test API key is not configured. Please add your sk_test_... key into config/config.local.php or STRIPE_SECRET_KEY environment variable. See docs/STRIPE_SETUP.md for instructions.'
            );
        }

        $postData = [
            'mode' => 'payment',
            'client_reference_id' => (string)$order['id'],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata[order_id]' => (string)$order['id'],
            'metadata[order_number]' => (string)$order['order_number'],
        ];

        // Format line items
        $i = 0;
        foreach ($items as $item) {
            $unitAmount = (int)round(((float)$item['unit_price']) * 100);
            $postData["line_items[{$i}][price_data][currency]"] = 'usd';
            $postData["line_items[{$i}][price_data][unit_amount]"] = (string)$unitAmount;
            $postData["line_items[{$i}][price_data][product_data][name]"] = $item['product_name'];
            $postData["line_items[{$i}][price_data][product_data][description]"] = 'SKU: ' . $item['sku'];
            $postData["line_items[{$i}][quantity]"] = (string)$item['quantity'];
            $i++;
        }

        // Add shipping fee as line item if greater than 0
        $shipping = (float)($order['shipping_cost'] ?? 0.0);
        if ($shipping > 0) {
            $postData["line_items[{$i}][price_data][currency]"] = 'usd';
            $postData["line_items[{$i}][price_data][unit_amount]"] = (string)((int)round($shipping * 100));
            $postData["line_items[{$i}][price_data][product_data][name]"] = 'Standard Cargo Shipping';
            $postData["line_items[{$i}][quantity]"] = '1';
        }

        $start = microtime(true);
        $ch = curl_init($this->apiBase . '/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $durationMs = round((microtime(true) - $start) * 1000, 2);
        Telemetry::recordEvent('stripe_api_call', [
            'endpoint' => '/v1/checkout/sessions',
            'http_code' => $httpCode,
            'duration_ms' => $durationMs,
        ]);

        if ($err) {
            throw new \RuntimeException("Stripe connection error: {$err}");
        }

        $decoded = json_decode((string)$response, true);
        if ($httpCode >= 400 || empty($decoded['id'])) {
            $msg = $decoded['error']['message'] ?? 'Unknown Stripe error';
            throw new \RuntimeException("Stripe API error ({$httpCode}): {$msg}");
        }

        return [
            'id' => $decoded['id'],
            'url' => $decoded['url'],
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        $start = microtime(true);
        $ch = curl_init($this->apiBase . '/checkout/sessions/' . urlencode($sessionId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $durationMs = round((microtime(true) - $start) * 1000, 2);
        Telemetry::recordEvent('stripe_api_retrieve', [
            'session_id' => $sessionId,
            'http_code' => $httpCode,
            'duration_ms' => $durationMs,
        ]);

        if ($err) {
            throw new \RuntimeException("Stripe connection error: {$err}");
        }

        $decoded = json_decode((string)$response, true);
        if ($httpCode >= 400) {
            $msg = $decoded['error']['message'] ?? 'Failed to retrieve Stripe session';
            throw new \RuntimeException("Stripe API error ({$httpCode}): {$msg}");
        }

        return $decoded;
    }

    /**
     * Refunds a payment intent. Mock/demo/simulated intents (seed data, demo gateway, webhook simulator) are refunded locally.
     * Returns the refund id.
     */
    public function refund(?string $paymentIntent): string
    {
        if (empty($paymentIntent) || preg_match('/^pi_(test_mock|demo|sim|cli)/', $paymentIntent) || !$this->isConfigured()) {
            return 're_demo_' . bin2hex(random_bytes(6));
        }

        $ch = curl_init($this->apiBase . '/refunds');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['payment_intent' => $paymentIntent]),
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->secretKey],
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        Telemetry::recordEvent('stripe_api_refund', ['payment_intent' => $paymentIntent, 'http_code' => $httpCode]);

        if ($err) {
            throw new \RuntimeException("Stripe connection error: {$err}");
        }
        $decoded = json_decode((string)$response, true);
        if ($httpCode >= 400 || empty($decoded['id'])) {
            throw new \RuntimeException('Stripe refund failed: ' . ($decoded['error']['message'] ?? 'unknown error'));
        }
        return (string)$decoded['id'];
    }

    public function hasWebhookSecret(): bool
    {
        return $this->webhookSecret !== '';
    }

    /**
     * Verifies a "Stripe-Signature" header (t=timestamp,v1=hmac) against the raw payload.
     * Mirrors Stripe's documented scheme: HMAC-SHA256 of "{t}.{payload}" with a 5 minute tolerance.
     */
    public function verifyWebhookSignature(string $payload, string $header, int $tolerance = 300): bool
    {
        if ($this->webhookSecret === '' || $header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2) {
                continue;
            }
            if ($kv[0] === 't') {
                $timestamp = (int)$kv[1];
            } elseif ($kv[0] === 'v1') {
                $signatures[] = $kv[1];
            }
        }

        if (!$timestamp || !$signatures || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = self::signPayload($payload, $this->webhookSecret, $timestamp);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }
        return false;
    }

    public static function signPayload(string $payload, string $secret, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    }
}
