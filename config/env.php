<?php
// config/env.php
// Central environment loader and configuration registry.

declare(strict_types=1);

$baseConfig = require __DIR__ . '/config.example.php';

// Merge local configuration file if present
$localConfigFile = __DIR__ . '/config.local.php';
if (file_exists($localConfigFile)) {
    $localConfig = require $localConfigFile;
    if (is_array($localConfig)) {
        $baseConfig = array_replace_recursive($baseConfig, $localConfig);
    }
}

// Override with Docker or OS environment variables if set
$dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? null);
if ($dbHost !== null && $dbHost !== '') {
    $baseConfig['db']['host'] = $dbHost;
}

$dbPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? null);
if ($dbPort !== null && $dbPort !== '') {
    $baseConfig['db']['port'] = (int)$dbPort;
}

$dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? null);
if ($dbName !== null && $dbName !== '') {
    $baseConfig['db']['database'] = $dbName;
}

$dbUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? null);
if ($dbUser !== null && $dbUser !== '') {
    $baseConfig['db']['username'] = $dbUser;
}

$dbPass = getenv('DB_PASS');
if ($dbPass === false) {
    $dbPass = $_ENV['DB_PASS'] ?? null;
}
if ($dbPass !== null) {
    $baseConfig['db']['password'] = (string)$dbPass;
}

$stripeSecret = getenv('STRIPE_SECRET_KEY') ?: ($_ENV['STRIPE_SECRET_KEY'] ?? null);
if ($stripeSecret !== null && $stripeSecret !== '') {
    $baseConfig['stripe']['secret_key'] = $stripeSecret;
}

$stripePublishable = getenv('STRIPE_PUBLISHABLE_KEY') ?: ($_ENV['STRIPE_PUBLISHABLE_KEY'] ?? null);
if ($stripePublishable !== null && $stripePublishable !== '') {
    $baseConfig['stripe']['publishable_key'] = $stripePublishable;
}

$webhookSecret = getenv('STRIPE_WEBHOOK_SECRET') ?: ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? null);
if ($webhookSecret !== null && $webhookSecret !== '') {
    $baseConfig['stripe']['webhook_secret'] = $webhookSecret;
}

$appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);
if ($appEnv !== null && $appEnv !== '') {
    $baseConfig['app_env'] = $appEnv;
}
if (($baseConfig['app_env'] ?? '') === 'production') {
    $baseConfig['demo_payments'] = false;
}

$appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? null);
if ($appUrl !== null && $appUrl !== '') {
    $baseConfig['app_url'] = rtrim($appUrl, '/');
}

return $baseConfig;
