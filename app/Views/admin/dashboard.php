<?php
// app/Views/admin/dashboard.php
?>
<div style="margin-bottom: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Admin Management Portal</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Store metrics, product catalog management, and order fulfillment.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?= url('admin/webhooks') ?>" class="btn btn-secondary">Webhook Console</a>
            <a href="<?= url('admin/products') ?>" class="btn btn-secondary">Manage Products</a>
            <a href="<?= url('admin/orders') ?>" class="btn btn-primary">Fulfillment & Orders</a>
        </div>
    </div>

    <!-- Store KPIs Grid -->
    <div class="kpi-grid">
        <div class="card kpi-card" style="--kpi-color: var(--primary);">
            <span class="kpi-label">Total Paid Revenue</span>
            <div class="kpi-value"><?= money($metrics['total_revenue']) ?></div>
            <div class="kpi-note">From <?= (int)$metrics['total_orders'] ?> settled orders</div>
        </div>
        <div class="card kpi-card" style="--kpi-color: var(--text-primary);">
            <span class="kpi-label">Active Catalog Items</span>
            <div class="kpi-value"><?= (int)$metrics['total_products'] ?></div>
            <div class="kpi-note"><?= (int)$metrics['total_stock'] ?> units in warehouse</div>
        </div>
        <div class="card kpi-card" style="--kpi-color: var(--accent);">
            <span class="kpi-label">Active Shipments</span>
            <div class="kpi-value"><?= (int)$metrics['active_shipments'] ?></div>
            <div class="kpi-note">Moving across delivery network</div>
        </div>
        <div class="card kpi-card" style="--kpi-color: var(--danger);">
            <span class="kpi-label">Needs Attention</span>
            <div class="kpi-value"><?= (int)$metrics['failed_deliveries'] ?></div>
            <div class="kpi-note">failed deliveries &bull; <?= (int)$metrics['cancelled_orders'] ?> cancelled/returned orders</div>
        </div>
        <div class="card kpi-card" style="--kpi-color: var(--gold);">
            <span class="kpi-label">Registered Customers</span>
            <div class="kpi-value"><?= (int)$metrics['total_customers'] ?></div>
            <div class="kpi-note">Verified user accounts</div>
        </div>
    </div>

    <!-- Analytics -->
    <?php
        $maxRevenue = max(1, ...array_column($revenueSeries, 'revenue'));
        $statusColors = ['placed' => '#f59e0b', 'packed' => '#818cf8', 'shipped' => '#4f46e5', 'delivered' => '#059669', 'cancelled' => '#dc2626', 'returned' => '#9f1239'];
        $statusTotal = max(1, array_sum($statusBreakdown));
    ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
        <div class="card" style="margin-bottom: 0;">
            <h3 style="font-size: 16px; font-weight: 700;">Paid Revenue &mdash; Last 7 Days</h3>
            <div class="bar-chart">
                <?php foreach ($revenueSeries as $point): ?>
                    <div class="bar-col" title="<?= e($point['date']) ?>: <?= (int)$point['orders'] ?> orders">
                        <span class="bar-value"><?= $point['revenue'] > 0 ? '$' . number_format($point['revenue'], 0) : '' ?></span>
                        <div class="bar" style="height: <?= round($point['revenue'] / $maxRevenue * 100) ?>%;"></div>
                        <span class="bar-label"><?= e($point['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0;">
            <h3 style="font-size: 16px; font-weight: 700;">Orders by Status</h3>
            <div class="stack-bar">
                <?php foreach ($statusBreakdown as $st => $cnt): ?>
                    <div style="width: <?= $cnt / $statusTotal * 100 ?>%; background: <?= $statusColors[$st] ?? '#94a3b8' ?>;" title="<?= e($st) ?>: <?= $cnt ?>"></div>
                <?php endforeach; ?>
            </div>
            <div class="legend">
                <?php foreach ($statusBreakdown as $st => $cnt): ?>
                    <span><i style="background: <?= $statusColors[$st] ?? '#94a3b8' ?>;"></i><?= e(ucfirst($st)) ?> (<?= $cnt ?>)</span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($failedShipments) || !empty($lowStock)): ?>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        <div class="card" style="margin-bottom: 0;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Failed Deliveries</h3>
            <?php if (empty($failedShipments)): ?>
                <p style="color: var(--text-muted); font-size: 13px;">No failed deliveries. 🎉</p>
            <?php endif; ?>
            <?php foreach ($failedShipments as $fs): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color); font-size: 13px;">
                    <span><a href="<?= url('tracking/track', ['code' => $fs['tracking_number']]) ?>" class="notranslate" translate="no"><strong><?= e($fs['tracking_number']) ?></strong></a> &middot; <?= e($fs['customer_name']) ?></span>
                    <?= status_badge('delivery_failed', $fs['delivery_attempts'] . '/' . \App\Models\Shipment::MAX_DELIVERY_ATTEMPTS . ' attempts') ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="card" style="margin-bottom: 0;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Low Stock Alerts</h3>
            <?php foreach ($lowStock as $ls): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color); font-size: 13px;">
                    <span><a href="<?= url('admin/products', ['edit_id' => $ls['id']]) ?>"><?= e($ls['name']) ?></a></span>
                    <?= status_badge((int)$ls['stock'] === 0 ? 'failed' : 'pending', (int)$ls['stock'] === 0 ? 'Out of stock' : $ls['stock'] . ' left') ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Orders & Couriers on Duty -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 16px; font-weight: 700;">Recent Customer Orders</h3>
                <a href="<?= url('admin/orders') ?>" style="font-size: 13px;">View All</a>
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: var(--bg-subtle); text-align: left; border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 10px 18px;">Order #</th>
                        <th style="padding: 10px 14px;">Customer</th>
                        <th style="padding: 10px 14px;">Total</th>
                        <th style="padding: 10px 14px;">Payment</th>
                        <th style="padding: 10px 14px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $ro): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px; font-weight: 600;">
                                <a href="<?= url('order/view', ['id' => $ro['id']]) ?>" class="notranslate" translate="no"><?= e($ro['order_number']) ?></a>
                            </td>
                            <td style="padding: 12px 14px;"><?= e($ro['customer_name']) ?></td>
                            <td style="padding: 12px 14px; font-weight: 600;"><?= money($ro['total_amount']) ?></td>
                            <td style="padding: 12px 14px;">
                                <?= status_badge($ro['payment_status']) ?>
                            </td>
                            <td style="padding: 12px 14px;"><?= status_badge($ro['order_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px;">Couriers On Duty</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($couriers as $courier): ?>
                    <div style="background: var(--bg-subtle); padding: 12px; border-radius: var(--radius-sm); font-size: 13px;">
                        <div style="font-weight: 600;"><?= e($courier['name']) ?></div>
                        <div style="color: var(--text-muted); font-size: 12px;"><?= e($courier['email']) ?> &bull; <?= e($courier['phone'] ?? 'No phone') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 16px;">
                <a href="<?= url('courier/dashboard') ?>" class="btn btn-secondary btn-sm btn-block">
                    Open Courier Logistics Portal
                </a>
            </div>
        </div>
    </div>
</div>
