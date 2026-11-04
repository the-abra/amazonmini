<?php
// app/Controllers/WebhookController.php
// Receives Stripe's asynchronous event notifications (POST ?route=webhook/stripe).

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Telemetry;
use App\Core\View;
use App\Services\StripeService;
use App\Services\StripeWebhookService;

class WebhookController
{
    public function stripe(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            View::json(['success' => false, 'message' => 'Method not allowed. Stripe sends POST requests.'], 405);
        }

        $config = require dirname(__DIR__, 2) . '/config/env.php';
        $stripe = new StripeService($config);
        $payload = (string)file_get_contents('php://input');
        $signature = (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

        if (!$stripe->hasWebhookSecret()) {
            View::json(['success' => false, 'message' => 'Webhook signing secret is not configured.'], 503);
        }

        if (!$stripe->verifyWebhookSignature($payload, $signature)) {
            Telemetry::logTerminalEndpoint('POST', 'webhook/stripe', 400, 'Invalid signature');
            View::json(['success' => false, 'message' => 'Invalid signature.'], 400);
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            View::json(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
        }

        $result = (new StripeWebhookService($config))->handle($event, 'stripe');

        View::json([
            'success' => $result['http'] < 400,
            'received' => true,
            'outcome' => $result['outcome'],
            'duplicate' => $result['duplicate'] ?? false,
        ], $result['http']);
    }
}
