<?php
// app/Views/order/index.php
?>
<div style="max-width: 960px; margin: 20px auto;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Your Orders</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Review purchases and check live shipment progress.</p>
        </div>
        <a href="<?= url('home') ?>" class="btn btn-secondary">Browse More Items</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card" style="text-align: center; padding: 50px 20px;">
            <svg style="margin: 0 auto 16px auto; color: var(--text-muted);" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            <h3 style="font-size: 18px; margin-bottom: 8px;">No orders found.</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Once you complete checkout with Stripe, your orders will appear here.</p>
            <a href="<?= url('home') ?>" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($orders as $order): ?>
                <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 0;">
                    <!-- Order Card Header -->
                    <div style="background: var(--bg-subtle); padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 13px; border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; gap: 24px;">
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11px;">ORDER PLACED</span>
                                <strong><?= date('M d, Y', strtotime($order['created_at'])) ?></strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11px;">TOTAL</span>
                                <strong><?= money($order['total_amount']) ?></strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11px;">SHIP TO</span>
                                <span><?= e($order['address_snapshot']['recipient_name'] ?? 'Recipient') ?></span>
                            </div>
                        </div>

                        <div>
                            <span style="color: var(--text-muted); font-size: 11px;">ORDER # </span>
                            <span class="notranslate" translate="no" style="font-weight: 700;"><?= e($order['order_number']) ?></span>
                        </div>
                    </div>

                    <!-- Order Card Body -->
                    <div style="padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <div style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">
                                Status: 
                                <?= status_badge($order['order_status']) ?>
                                <?php if (($order['shipment_status'] ?? '') === 'delivery_failed'): ?>
                                    <?= status_badge('delivery_failed', 'Delivery attempt failed') ?>
                                <?php endif; ?>
                            </div>

                            <div style="font-size: 13px; color: var(--text-muted);">
                                Payment: 
                                <?= status_badge($order['payment_status']) ?>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <?php if (!empty($order['tracking_number'])): ?>
                                <a href="<?= url('tracking/track', ['code' => $order['tracking_number']]) ?>" class="btn btn-primary btn-sm">
                                    Track Cargo (<?= e($order['tracking_number']) ?>)
                                </a>
                            <?php endif; ?>
                            <a href="<?= url('order/view', ['id' => $order['id']]) ?>" class="btn btn-secondary btn-sm">
                                View Invoice
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
