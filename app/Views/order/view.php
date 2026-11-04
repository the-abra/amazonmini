<?php
// app/Views/order/view.php
?>
<div style="max-width: 900px; margin: 20px auto;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Order Details</h1>
            <p style="color: var(--text-muted); font-size: 14px;">
                Ordered on <?= date('F d, Y \a\t H:i', strtotime($order['created_at'])) ?> &bull; 
                Order # <span class="notranslate" translate="no"><?= e($order['order_number']) ?></span>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <?php if ($shipment): ?>
                <a href="<?= url('tracking/track', ['code' => $shipment['tracking_number']]) ?>" class="btn btn-primary">
                    Track Shipment (<?= e($shipment['tracking_number']) ?>)
                </a>
            <?php endif; ?>
            <?php if (!empty($canCancel)): ?>
                <form action="<?= url('order/cancel') ?>" method="POST" onsubmit="return confirm('Cancel this order? <?= $order['payment_status'] === 'paid' ? 'Your payment will be refunded.' : '' ?>');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                    <button type="submit" class="btn btn-danger-outline">Cancel Order</button>
                </form>
            <?php endif; ?>
            <a href="<?= url('order/list') ?>" class="btn btn-secondary">All Orders</a>
        </div>
    </div>

    <?php if (in_array($order['order_status'], ['cancelled', 'returned'], true)): ?>
        <div class="banner banner-danger">
            <div>
                <strong>This order was <?= $order['order_status'] === 'returned' ? 'returned to sender' : 'cancelled' ?>.</strong>
                <?= e($order['cancel_reason'] ?? '') ?>
                <?php if ($order['payment_status'] === 'refunded'): ?>
                    Your payment has been refunded<?= !empty($order['refund_id']) ? ' (ref. <span class="mono">' . e($order['refund_id']) . '</span>)' : '' ?> and reserved stock was released.
                <?php elseif ($order['payment_status'] === 'paid'): ?>
                    A manual refund is being processed by our team.
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($shipment && $shipment['current_status'] === 'delivery_failed'): ?>
        <div class="banner banner-warning">
            <div>
                <strong>We could not deliver your package.</strong>
                Attempt <?= (int)$shipment['delivery_attempts'] ?> of <?= \App\Models\Shipment::MAX_DELIVERY_ATTEMPTS ?> failed. The courier will try again tomorrow; please make sure someone is available at the address.
            </div>
        </div>
    <?php endif; ?>

    <!-- Summary Overview Card -->
    <div class="card" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div>
            <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Shipping Address</span>
            <div style="font-size: 14px; margin-top: 6px; line-height: 1.4;">
                <strong><?= e($order['address_snapshot']['recipient_name'] ?? '') ?></strong><br>
                <?= e($order['address_snapshot']['address_line'] ?? '') ?><br>
                <?= e($order['address_snapshot']['city'] ?? '') ?>, <?= e($order['address_snapshot']['postal_code'] ?? '') ?><br>
                <?= e($order['address_snapshot']['country'] ?? '') ?><br>
                Phone: <?= e($order['address_snapshot']['phone'] ?? '') ?>
            </div>
        </div>

        <div>
            <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Payment Information</span>
            <div style="font-size: 14px; margin-top: 6px; line-height: 1.4;">
                Status: <?= status_badge($order['payment_status']) ?><br>
                Provider: Stripe Checkout Sandbox<br>
                <?php if (!empty($order['stripe_session_id'])): ?>
                    <span style="font-size: 11px; color: var(--text-muted); word-break: break-all;">Session: <?= e($order['stripe_session_id']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Shipment Status</span>
            <div style="font-size: 14px; margin-top: 6px; line-height: 1.4;">
                Status: <?= status_badge($order['order_status']) ?><br>
                Carrier: Amazon Logistics Express<br>
                <?php if ($shipment): ?>
                    Tracking: <a href="<?= url('tracking/track', ['code' => $shipment['tracking_number']]) ?>" class="notranslate" translate="no" style="font-weight: 700;"><?= e($shipment['tracking_number']) ?></a>
                <?php else: ?>
                    <span style="color: var(--text-muted);">Awaiting Warehouse Dispatch</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Items List Table Card -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 16px;">
            Purchased Line Items
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: var(--bg-subtle); text-align: left; border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 20px;">Item</th>
                    <th style="padding: 12px 14px;">SKU</th>
                    <th style="padding: 12px 14px; text-align: right;">Price</th>
                    <th style="padding: 12px 14px; text-align: center;">Qty</th>
                    <th style="padding: 12px 20px; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 14px 20px; display: flex; align-items: center; gap: 12px;">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['product_name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php endif; ?>
                            <div>
                                <a href="<?= url('product/view', ['id' => $item['product_id']]) ?>" style="font-weight: 600; color: inherit;">
                                    <?= e($item['product_name']) ?>
                                </a>
                            </div>
                        </td>
                        <td style="padding: 14px 14px; font-size: 13px; color: var(--text-muted);" class="notranslate" translate="no">
                            <?= e($item['sku']) ?>
                        </td>
                        <td style="padding: 14px 14px; text-align: right;">
                            <?= money($item['unit_price']) ?>
                        </td>
                        <td style="padding: 14px 14px; text-align: center;">
                            <?= (int)$item['quantity'] ?>
                        </td>
                        <td style="padding: 14px 20px; text-align: right; font-weight: 700;">
                            <?= money($item['line_total']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals Calculation -->
        <div style="padding: 20px; background: var(--bg-subtle); display: flex; justify-content: flex-end;">
            <div style="width: 280px; font-size: 14px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--text-secondary);">
                    <span>Subtotal:</span>
                    <span><?= money($order['subtotal']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--text-secondary);">
                    <span>Shipping:</span>
                    <span><?= ($order['shipping_cost'] > 0) ? money($order['shipping_cost']) : 'FREE' ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 800; border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 6px;">
                    <span>Grand Total:</span>
                    <span><?= money($order['total_amount']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
