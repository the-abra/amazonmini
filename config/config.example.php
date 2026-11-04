<?php
// config/config.example.php
// Template configuration file for Amazon Mini Clone.
// For local development or InfinityFree hosting, copy this file to:
// config/config.local.php (which is automatically ignored by Git).

return [
    'app_name' => 'Amazon Mini Clone',
    'app_url' => 'http://localhost:8080',
    'app_env' => 'development', // 'development' | 'production'
    'currency' => 'USD',
    'currency_symbol' => '$',
    'items_per_page' => 12,
    // Demo gateway lets the app be tried without Stripe keys (never enabled in production).
    'demo_payments' => true,
    
    // Database credentials (MySQL 5.7+ / MariaDB 10.4+)
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'ecommerce_db',
        'username' => 'ecom_user',
        'password' => 'ecom_pass',
        'charset' => 'utf8mb4',
    ],

    // Stripe Test Sandbox Credentials
    // Get your test keys from: https://dashboard.stripe.com/test/apikeys
    // IMPORTANT: Use test keys (sk_test_... and pk_test_...) for development
    // Never commit real secret keys to version control
    'stripe' => [
        'secret_key' => 'sk_test_YOUR_SECRET_KEY_HERE',
        'publishable_key' => 'pk_test_YOUR_PUBLISHABLE_KEY_HERE',
        'webhook_secret' => 'whsec_YOUR_WEBHOOK_SECRET_HERE',
    ],
    
    // Telemetry settings (Browser console logging)
    'telemetry' => [
        'enabled' => true,
        'log_to_console' => true,
        'terminal_level' => 'endpoints_and_errors', // dev terminal stays clean
    ],
];
