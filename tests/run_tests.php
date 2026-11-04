<?php
/**
 * tests/run_tests.php
 * Automated Unit, Integration & Workflow Test Suite
 * Designed for educational inspection and CI verification.
 */

declare(strict_types=1);

$rootDir = dirname(__DIR__);
require_once $rootDir . '/app/Core/Bootstrap.php';
$config = require $rootDir . '/config/env.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Telemetry;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Wishlist;
use App\Core\Paginator;
use App\Services\OrderService;
use App\Services\StripeService;
use App\Services\StripeWebhookService;

Database::configure($config);
Telemetry::init($config);

$passed = 0;
$failed = 0;

function it(string $description, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "\033[32m  [PASS]\033[0m {$description}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "\033[31m  [FAIL]\033[0m {$description}\n";
        echo "         Error: " . $e->getMessage() . "\n";
        echo "         Location: " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

function expectTrue(bool $condition, string $msg = 'Expected value to be true'): void
{
    if (!$condition) {
        throw new \RuntimeException($msg);
    }
}

function expectEquals(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        $msg = $msg ?: sprintf('Expected %s but got %s', var_export($expected, true), var_export($actual, true));
        throw new \RuntimeException($msg);
    }
}

echo "\n======================================================\n";
echo "  Amazon Mini Clone — Test Suite Execution\n";
echo "======================================================\n\n";

// --------------------------------------------------------------------------
// 1. Root Directory Constraint Assertion
// --------------------------------------------------------------------------
echo "\033[34m[Section 1: Architecture & Root Policy Constraints]\033[0m\n";

it('Enforces strict root directory constraint (strictly only index.php, Makefile, LICENSE, README.md)', function () use ($rootDir) {
    $entries = scandir($rootDir);
    $rootFiles = [];

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || $entry === '.git') {
            continue;
        }
        $fullPath = $rootDir . '/' . $entry;
        if (!is_dir($fullPath)) {
            $rootFiles[] = $entry;
        }
    }

    sort($rootFiles);
    $allowedFiles = ['LICENSE', 'Makefile', 'README.md', 'index.php'];
    sort($allowedFiles);

    expectEquals(
        $allowedFiles,
        $rootFiles,
        'Root directory contains unexpected files! Allowed files: ' . implode(', ', $allowedFiles) . ' | Found: ' . implode(', ', $rootFiles)
    );
});

it('Verifies .git/info/exclude exists and ignores sensitive local credentials', function () use ($rootDir) {
    $excludePath = $rootDir . '/.git/info/exclude';
    expectTrue(file_exists($excludePath), '.git/info/exclude must exist');
    $content = file_get_contents($excludePath);
    expectTrue(str_contains($content, 'config/config.local.php'), 'Must ignore config/config.local.php');
});

// --------------------------------------------------------------------------
// 2. Database Connection & Seed Data Integrity
// --------------------------------------------------------------------------
echo "\n\033[34m[Section 2: Database Connection & Seed Integrity]\033[0m\n";

it('Connects to MySQL/MariaDB database and verifies schema tables', function () {
    $tables = Database::fetchAll('SHOW TABLES');
    expectTrue(count($tables) >= 10, 'Database should contain at least 10 relational tables');

    $tableNames = [];
    foreach ($tables as $t) {
        $tableNames[] = array_values($t)[0];
    }

    $requiredTables = ['users', 'addresses', 'categories', 'products', 'reviews', 'cart_items', 'wishlist_items', 'orders', 'order_items', 'shipments', 'shipment_events'];
    foreach ($requiredTables as $rt) {
        expectTrue(in_array($rt, $tableNames, true), "Required table '{$rt}' must exist in schema");
    }
});

it('Verifies demo user accounts and bcrypt verification with Password123!', function () {
    $customer = User::findByEmail('customer@example.com');
    expectTrue($customer !== null, 'Demo customer must exist');
    expectEquals('customer', $customer['role']);
    expectTrue(password_verify('Password123!', $customer['password_hash']), 'Customer password verification');

    $admin = User::findByEmail('admin@example.com');
    expectTrue($admin !== null, 'Demo admin must exist');
    expectEquals('admin', $admin['role']);
    expectTrue(password_verify('Password123!', $admin['password_hash']), 'Admin password verification');

    $courier = User::findByEmail('courier@example.com');
    expectTrue($courier !== null, 'Demo courier must exist');
    expectEquals('courier', $courier['role']);
    expectTrue(password_verify('Password123!', $courier['password_hash']), 'Courier password verification');
});

// --------------------------------------------------------------------------
// 3. Catalog, Product Search & Reviews
// --------------------------------------------------------------------------
echo "\n\033[34m[Section 3: Catalog, Search & Customer Reviews]\033[0m\n";

it('Searches and filters products by keywords and category', function () {
    $products = Product::search(['query' => 'Headphones']);
    expectTrue(count($products) >= 1, 'Should find products matching Headphones');
    expectTrue(str_contains($products[0]['name'], 'Headphones'), 'Found product name match');

    $electronics = Category::findBySlug('electronics-gadgets');
    expectTrue($electronics !== null, 'Electronics category exists');

    $catProducts = Product::search(['category_id' => $electronics['id']]);
    expectTrue(count($catProducts) >= 1, 'Category search returns products');
});

it('Submits review and recalculates product rating average', function () {
    $product = Product::findById(1);
    $initialCount = (int)$product['review_count'];

    // Create unique test customer to submit review
    $testEmail = 'reviewer_' . uniqid() . '@example.com';
    $userId = User::create([
        'name' => 'Reviewer Bot',
        'email' => $testEmail,
        'password' => 'Password123!',
        'role' => 'customer'
    ]);

    $reviewId = Review::create([
        'product_id' => 1,
        'user_id' => $userId,
        'rating' => 5,
        'title' => 'Automated Integration Review',
        'comment' => 'Exceptional build quality and audio clarity.'
    ]);
    expectTrue($reviewId > 0, 'Review inserted successfully');

    $updatedProduct = Product::findById(1);
    expectEquals($initialCount + 1, (int)$updatedProduct['review_count'], 'Review count incremented');
});

// --------------------------------------------------------------------------
// 4. Cart, Wishlist & Free Shipping Threshold
// --------------------------------------------------------------------------
echo "\n\033[34m[Section 4: Shopping Cart & Free Shipping Calculations]\033[0m\n";

it('Manages persistent cart items and applies free shipping over $50', function () {
    $testEmail = 'cartuser_' . uniqid() . '@example.com';
    $userId = User::create([
        'name' => 'Cart Tester',
        'email' => $testEmail,
        'password' => 'Password123!',
        'role' => 'customer'
    ]);

    // Add cheap item (under $50)
    Cart::add($userId, 8, 1); // Artisan Vegan Leather Desk Mat ($29.99)
    $summary = Cart::getSummary($userId);
    expectEquals(29.99, $summary['subtotal']);
    expectEquals(4.99, $summary['shipping'], 'Shipping should be $4.99 under $50 threshold');
    expectEquals(34.98, $summary['total']);

    // Add second item so subtotal >= $50
    Cart::add($userId, 8, 1); // Subtotal = $59.98
    $summaryFree = Cart::getSummary($userId);
    expectEquals(59.98, $summaryFree['subtotal']);
    expectEquals(0.0, $summaryFree['shipping'], 'Shipping should be FREE ($0.00) when >= $50');
    expectEquals(59.98, $summaryFree['total']);

    // Clear cart
    Cart::clear($userId);
    expectEquals(0, Cart::count($userId), 'Cart is now empty');
});

it('Toggles items in wishlist', function () {
    $customer = User::findByEmail('customer@example.com');
    Database::execute('DELETE FROM wishlist_items WHERE user_id = ? AND product_id = ?', [$customer['id'], 2]);

    $res1 = Wishlist::toggle($customer['id'], 2);
    expectTrue($res1['in_wishlist'], 'Item added to wishlist');

    expectTrue(Wishlist::isInWishlist($customer['id'], 2), 'Wishlist membership check');

    $res2 = Wishlist::toggle($customer['id'], 2);
    expectTrue(!$res2['in_wishlist'], 'Item removed from wishlist');
});

// --------------------------------------------------------------------------
// 5. End-to-End Order, Admin Packing & Courier Cargo Lifecycle
// --------------------------------------------------------------------------
echo "\n\033[34m[Section 5: End-to-End Cargo Tracking State Machine]\033[0m\n";

it('Executes complete cargo lifecycle: Order -> Pack -> Pick Up -> In Transit -> Out for Delivery -> Delivered', function () {
    $customer = User::findByEmail('customer@example.com');
    $courier = User::findByEmail('courier@example.com');
    $address = Address::getDefault($customer['id']);
    expectTrue($address !== null, 'Customer default address exists');

    // 1. Create Order
    Cart::clear($customer['id']);
    Cart::add($customer['id'], 1, 1); // $199.99
    $cartSummary = Cart::getSummary($customer['id']);

    $orderId = Order::createFromCart($customer['id'], $address, $cartSummary);
    expectTrue($orderId > 0, 'Order created in database');
    $order = Order::findById($orderId);
    expectEquals('placed', $order['order_status']);
    expectEquals('pending', $order['payment_status']);

    // 2. Mark Paid (Simulating Stripe Success Callback)
    $paid = Order::markPaid($orderId, 'cs_test_mock_session_' . uniqid(), 'pi_test_mock_intent_' . uniqid());
    expectTrue($paid, 'Order marked as paid and stock deducted');
    $orderAfterPay = Order::findById($orderId);
    expectEquals('paid', $orderAfterPay['payment_status']);

    // 3. Admin Packs Order & Generates Tracking Number
    $shipment = Shipment::createForOrder($orderId, $courier['id']);
    expectTrue($shipment !== null, 'Shipment created');
    expectTrue(str_starts_with($shipment['tracking_number'], 'TRK-'), 'Valid tracking identifier format');
    expectEquals('awaiting_pickup', $shipment['current_status']);

    $orderAfterPack = Order::findById($orderId);
    expectEquals('packed', $orderAfterPack['order_status']);

    // 4. Courier Step 2: Picked Up
    Shipment::updateStatus((int)$shipment['id'], 'picked_up', 'Amazon BFI4 Warehouse, Seattle WA', 'Courier scanned barcode at bay 3', $courier['id']);
    $s1 = Shipment::findByTrackingNumber($shipment['tracking_number']);
    expectEquals('picked_up', $s1['current_status']);

    // 5. Courier Step 3: In Transit
    Shipment::updateStatus((int)$shipment['id'], 'in_transit', 'Regional Sorting Center, Kent WA', 'Sorted into distribution route', $courier['id']);
    $s2 = Shipment::findByTrackingNumber($shipment['tracking_number']);
    expectEquals('in_transit', $s2['current_status']);

    // 6. Courier Step 4: Out for Delivery
    Shipment::updateStatus((int)$shipment['id'], 'out_for_delivery', 'Delivery Station #DSE4, Seattle WA', 'Loaded on courier van', $courier['id']);
    $s3 = Shipment::findByTrackingNumber($shipment['tracking_number']);
    expectEquals('out_for_delivery', $s3['current_status']);

    // 7. Courier Step 5: Delivered
    Shipment::updateStatus((int)$shipment['id'], 'delivered', 'Front Porch, Seattle WA 98101', 'Handed directly to resident', $courier['id']);
    $s4 = Shipment::findByTrackingNumber($shipment['tracking_number']);
    expectEquals('delivered', $s4['current_status']);

    // Verify parent order updated to delivered
    $finalOrder = Order::findById($orderId);
    expectEquals('delivered', $finalOrder['order_status']);

    // Verify audit events history contains all 5 steps
    $events = Shipment::getEvents((int)$shipment['id']);
    expectEquals(5, count($events), 'Audit history contains all 5 checkpoint events');
});

// --------------------------------------------------------------------------
// 6. Telemetry & Stripe Integration Formatting
// --------------------------------------------------------------------------
echo "\n\033[34m[Section 6: Telemetry Bridge & Stripe Service Formulation]\033[0m\n";

it('Generates structured backend telemetry JSON payload for browser console', function () {
    Telemetry::recordEvent('test_event', ['meta' => 123]);
    $payload = Telemetry::getPayload();

    expectTrue(isset($payload['request']['duration_ms']), 'Telemetry includes request duration');
    expectTrue(isset($payload['database']['query_count']), 'Telemetry includes SQL query count');
    expectTrue(is_array($payload['database']['queries']), 'Telemetry includes queries array');

    $json = Telemetry::renderJson();
    expectTrue(str_contains($json, 'duration_ms'), 'Valid serialized JSON payload');
});

it('Handles Stripe Service configuration and validation gracefully', function () use ($config) {
    $unconfiguredService = new StripeService(['stripe' => ['secret_key' => 'placeholder_key', 'publishable_key' => '']]);
    expectTrue(!$unconfiguredService->isConfigured(), 'Placeholder Stripe key is detected as unconfigured');

    try {
        $unconfiguredService->createCheckoutSession(['id' => 1, 'order_number' => 'ORD-1'], [], 'http://localhost/success', 'http://localhost/cancel');
        throw new \RuntimeException('Should have thrown when unconfigured');
    } catch (\RuntimeException $e) {
        expectTrue(str_contains($e->getMessage(), 'Stripe Test API key is not configured'), 'Helpful error message provided');
    }

    $activeService = new StripeService($config);
    if (!str_contains($config['stripe']['secret_key'], 'placeholder')) {
        expectTrue($activeService->isConfigured(), 'Configured Stripe key is correctly detected as active');
    }
});


// --------------------------------------------------------------------------
// 7. Pagination
// --------------------------------------------------------------------------
echo "\n\033[34m[Section 7: Catalog Pagination]\033[0m\n";

it('Paginator clamps pages and builds an ellipsis window', function () {
    $p = new Paginator(100, 12, 99);
    expectEquals(9, $p->totalPages);
    expectEquals(9, $p->page, 'Out-of-range page is clamped to the last page');
    expectEquals(96, $p->offset());
    expectEquals(1, (new Paginator(5, 12, -3))->page, 'Negative page is clamped to 1');
    expectEquals([1, null, 4, 5, 6, null, 9], (new Paginator(100, 12, 5))->window(1));
    expectEquals(0, (new Paginator(0, 12, 1))->firstItem());
});

it('Product::search honours limit/offset and count() matches the catalog', function () {
    $total = Product::count([]);
    expectTrue($total >= 25, 'Seed data provides enough products for 3 pages');
    $page1 = Product::search(['limit' => 12, 'offset' => 0]);
    $page2 = Product::search(['limit' => 12, 'offset' => 12]);
    expectEquals(12, count($page1));
    expectEquals([], array_intersect(array_column($page1, 'id'), array_column($page2, 'id')), 'Pages never overlap');
    expectEquals(Product::count(['query' => 'keyboard']), count(Product::search(['query' => 'keyboard'])));
});

// --------------------------------------------------------------------------
// 8. Stock conflicts, cancellation & refunds
// --------------------------------------------------------------------------
echo "\n\033[34m[Section 8: Stock Conflicts & Order Cancellation]\033[0m\n";

function makeOrder(int $productId, int $qty): array
{
    $customer = User::findByEmail('customer@example.com');
    Cart::clear($customer['id']);
    Cart::add($customer['id'], $productId, $qty);
    $orderId = Order::createFromCart($customer['id'], Address::getDefault($customer['id']), Cart::getSummary($customer['id']));
    return [$orderId, $customer];
}

it('Cancels an out-of-stock order at payment time, refunds and keeps stock intact', function () use ($config) {
    [$orderId] = makeOrder(5, 2);
    Database::execute('UPDATE products SET stock = 1 WHERE id = 5');

    $result = (new OrderService($config))->settlePayment($orderId, 'cs_test_oos_' . uniqid(), 'pi_test_mock_oos');
    expectEquals('out_of_stock', $result['status']);

    $order = Order::findById($orderId);
    expectEquals('cancelled', $order['order_status']);
    expectEquals('refunded', $order['payment_status']);
    expectTrue(str_contains($order['cancel_reason'], 'Out of stock'), 'Reason is recorded');
    expectEquals(1, (int)Product::findById(5)['stock'], 'Stock is not partially decremented');
    expectTrue(Shipment::findByOrderId($orderId) === null, 'No shipment is created');

    Database::execute('UPDATE products SET stock = 120 WHERE id = 5');
});

it('Lets a customer cancel a paid, unshipped order: restock + refund + shipment cancelled', function () use ($config) {
    [$orderId] = makeOrder(5, 2);
    $service = new OrderService($config);
    $before = (int)Product::findById(5)['stock'];

    expectEquals('paid', $service->settlePayment($orderId, 'cs_test_c_' . uniqid(), 'pi_test_mock_c')['status']);
    expectEquals($before - 2, (int)Product::findById(5)['stock']);

    $res = $service->cancelOrder($orderId, 'Cancelled by customer');
    expectTrue($res['ok'], $res['message']);
    $order = Order::findById($orderId);
    expectEquals('cancelled', $order['order_status']);
    expectEquals('refunded', $order['payment_status']);
    expectEquals($before, (int)Product::findById(5)['stock'], 'Stock restored');
    expectEquals('cancelled', Shipment::findByOrderId($orderId)['current_status']);

    $again = $service->cancelOrder($orderId, 'twice');
    expectTrue(!$again['ok'], 'A cancelled order cannot be cancelled again');
});

it('Refuses to cancel an order that has already shipped', function () use ($config) {
    [$orderId] = makeOrder(5, 1);
    $service = new OrderService($config);
    $service->settlePayment($orderId, 'cs_test_s_' . uniqid(), 'pi_test_mock_s');
    $shipment = Shipment::findByOrderId($orderId);
    Shipment::updateStatus((int)$shipment['id'], 'picked_up', 'Hub', 'scan', null);
    expectTrue(!$service->cancelOrder($orderId, 'too late')['ok'], 'Shipped orders are not cancellable');
});

// --------------------------------------------------------------------------
// 9. Delivery failure checkpoints
// --------------------------------------------------------------------------
echo "\n\033[34m[Section 9: Failed Delivery Checkpoints]\033[0m\n";

it('Enforces the shipment state machine and a failure reason', function () use ($config) {
    [$orderId] = makeOrder(5, 1);
    (new OrderService($config))->settlePayment($orderId, 'cs_test_t_' . uniqid(), 'pi_test_mock_t');
    $sid = (int)Shipment::findByOrderId($orderId)['id'];

    try {
        Shipment::updateStatus($sid, 'delivered', 'Door', 'skipped steps', null);
        throw new \RuntimeException('Illegal transition was accepted');
    } catch (\InvalidArgumentException $e) {
        expectTrue(str_contains($e->getMessage(), 'Illegal transition'));
    }

    Shipment::updateStatus($sid, 'picked_up', 'Hub', 'x', null);
    Shipment::updateStatus($sid, 'in_transit', 'Hub', 'x', null);
    Shipment::updateStatus($sid, 'out_for_delivery', 'Van', 'x', null);
    try {
        Shipment::updateStatus($sid, 'delivery_failed', 'Door', 'x', null, null);
        throw new \RuntimeException('Failure without reason was accepted');
    } catch (\InvalidArgumentException $e) {
        expectTrue(str_contains($e->getMessage(), 'reason'));
    }
});

it('Records failed attempts, allows retry, then returns to sender after 3 failures with refund + restock', function () use ($config) {
    [$orderId] = makeOrder(5, 1);
    $service = new OrderService($config);
    $service->settlePayment($orderId, 'cs_test_r_' . uniqid(), 'pi_test_mock_r');
    $stockAfterSale = (int)Product::findById(5)['stock'];
    $sid = (int)Shipment::findByOrderId($orderId)['id'];

    foreach (['picked_up', 'in_transit', 'out_for_delivery'] as $st) {
        Shipment::updateStatus($sid, $st, 'Hub', 'x', null);
    }

    Shipment::updateStatus($sid, 'delivery_failed', 'Door', 'Nobody home', null, 'RECIPIENT_ABSENT');
    $s = Shipment::findByOrderId($orderId);
    expectEquals('delivery_failed', $s['current_status']);
    expectEquals(1, (int)$s['delivery_attempts']);
    expectEquals('shipped', Order::findById($orderId)['order_status'], 'Order stays shipped while retries remain');

    Shipment::updateStatus($sid, 'out_for_delivery', 'Van', 'Second attempt', null);
    Shipment::updateStatus($sid, 'delivery_failed', 'Door', 'Wrong address', null, 'ADDRESS_NOT_FOUND');
    Shipment::updateStatus($sid, 'out_for_delivery', 'Van', 'Third attempt', null);
    Shipment::updateStatus($sid, 'delivery_failed', 'Door', 'Refused', null, 'RECIPIENT_REFUSED');

    $s = Shipment::findByOrderId($orderId);
    expectEquals('returned_to_sender', $s['current_status'], 'Third failure auto-returns the parcel');
    expectEquals(3, (int)$s['delivery_attempts']);

    $service->processReturn($orderId);
    $order = Order::findById($orderId);
    expectEquals('returned', $order['order_status']);
    expectEquals('refunded', $order['payment_status']);
    expectEquals($stockAfterSale + 1, (int)Product::findById(5)['stock'], 'Returned stock is restocked');
});

// --------------------------------------------------------------------------
// 10. Stripe webhooks
// --------------------------------------------------------------------------
echo "\n\033[34m[Section 10: Stripe Webhook Handler]\033[0m\n";

it('Verifies Stripe-Signature headers (valid, tampered, expired)', function () {
    $svc = new StripeService(['stripe' => ['secret_key' => 'x', 'webhook_secret' => 'whsec_test']]);
    $payload = '{"id":"evt_1"}';
    $t = time();
    $sig = StripeService::signPayload($payload, 'whsec_test', $t);

    expectTrue($svc->verifyWebhookSignature($payload, "t={$t},v1={$sig}"), 'Valid signature accepted');
    expectTrue(!$svc->verifyWebhookSignature($payload . ' ', "t={$t},v1={$sig}"), 'Tampered payload rejected');
    expectTrue(!$svc->verifyWebhookSignature($payload, "t={$t},v1=deadbeef"), 'Wrong signature rejected');
    $old = $t - 3600;
    $oldSig = StripeService::signPayload($payload, 'whsec_test', $old);
    expectTrue(!$svc->verifyWebhookSignature($payload, "t={$old},v1={$oldSig}"), 'Replay outside tolerance rejected');
});

it('Settles a pending order from checkout.session.completed and ignores a replayed event', function () use ($config) {
    [$orderId] = makeOrder(5, 1);
    $handler = new StripeWebhookService($config);
    $event = StripeWebhookService::buildSimulatedEvent('checkout.session.completed', Order::findById($orderId));

    $r1 = $handler->handle($event, 'simulated');
    expectEquals(200, $r1['http']);
    expectEquals('paid', Order::findById($orderId)['payment_status']);
    expectTrue(Shipment::findByOrderId($orderId) !== null, 'Shipment created by webhook');

    $stock = (int)Product::findById(5)['stock'];
    $r2 = $handler->handle($event, 'simulated');
    expectTrue($r2['duplicate'] ?? false, 'Replay is flagged as duplicate');
    expectEquals($stock, (int)Product::findById(5)['stock'], 'Replay does not deduct stock twice');
});

it('Handles expired sessions, failed payments and dashboard refunds via webhook', function () use ($config) {
    $handler = new StripeWebhookService($config);

    [$expired] = makeOrder(5, 1);
    $handler->handle(StripeWebhookService::buildSimulatedEvent('checkout.session.expired', Order::findById($expired)), 'simulated');
    expectEquals('cancelled', Order::findById($expired)['order_status']);

    [$failed] = makeOrder(5, 1);
    $handler->handle(StripeWebhookService::buildSimulatedEvent('payment_intent.payment_failed', Order::findById($failed)), 'simulated');
    expectEquals('failed', Order::findById($failed)['payment_status']);

    [$paid] = makeOrder(5, 1);
    $handler->handle(StripeWebhookService::buildSimulatedEvent('checkout.session.completed', Order::findById($paid)), 'simulated');
    $stock = (int)Product::findById(5)['stock'];
    $handler->handle(StripeWebhookService::buildSimulatedEvent('charge.refunded', Order::findById($paid)), 'simulated');
    $o = Order::findById($paid);
    expectEquals('refunded', $o['payment_status']);
    expectEquals('cancelled', $o['order_status']);
    expectEquals($stock + 1, (int)Product::findById(5)['stock'], 'Refund restocks inventory');
});

it('Reports an out-of-stock conflict when the webhook arrives after stock ran out', function () use ($config) {
    [$orderId] = makeOrder(5, 3);
    Database::execute('UPDATE products SET stock = 0 WHERE id = 5');
    $res = (new StripeWebhookService($config))->handle(StripeWebhookService::buildSimulatedEvent('checkout.session.completed', Order::findById($orderId)), 'simulated');
    expectEquals(200, $res['http'], 'Webhook still acknowledges the event');
    expectTrue(str_contains($res['outcome'], 'out_of_stock'));
    expectEquals('refunded', Order::findById($orderId)['payment_status']);
    Database::execute('UPDATE products SET stock = 120 WHERE id = 5');
});

echo "\n======================================================\n";
echo "  Test Results: \033[32m{$passed} Passed\033[0m, \033[" . ($failed > 0 ? '31' : '32') . "m{$failed} Failed\033[0m\n";
echo "======================================================\n\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
