<?php
// app/Views/admin/orders.php
?>
<div style="margin-bottom: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Order Fulfillment & Dispatch</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Pack customer orders, generate tracking identifiers, and assign to logistics couriers.</p>
        </div>
        <a href="<?= url('admin/dashboard') ?>" class="btn btn-secondary">Dashboard</a>
    </div>

    <!-- Status Filters -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="<?= url('admin/orders') ?>" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?>">All Orders</a>
        <a href="<?= url('admin/orders', ['status' => 'placed']) ?>" class="btn btn-sm <?= ($statusFilter === 'placed') ? 'btn-primary' : 'btn-secondary' ?>">Unfulfilled (Placed)</a>
        <a href="<?= url('admin/orders', ['status' => 'packed']) ?>" class="btn btn-sm <?= ($statusFilter === 'packed') ? 'btn-primary' : 'btn-secondary' ?>">Packed</a>
        <a href="<?= url('admin/orders', ['status' => 'shipped']) ?>" class="btn btn-sm <?= ($statusFilter === 'shipped') ? 'btn-primary' : 'btn-secondary' ?>">In Transit</a>
        <a href="<?= url('admin/orders', ['status' => 'delivered']) ?>" class="btn btn-sm <?= ($statusFilter === 'delivered') ? 'btn-primary' : 'btn-secondary' ?>">Delivered</a>
        <a href="<?= url('admin/orders', ['status' => 'cancelled']) ?>" class="btn btn-sm <?= ($statusFilter === 'cancelled') ? 'btn-primary' : 'btn-secondary' ?>">Cancelled</a>
        <a href="<?= url('admin/orders', ['status' => 'returned']) ?>" class="btn btn-sm <?= ($statusFilter === 'returned') ? 'btn-primary' : 'btn-secondary' ?>">Returned</a>
    </div>

    <!-- Orders Table Card -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: var(--bg-subtle); text-align: left; border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 18px;">Order #</th>
                    <th style="padding: 12px 14px;">Date</th>
                    <th style="padding: 12px 14px;">Customer</th>
                    <th style="padding: 12px 14px;">Total</th>
                    <th style="padding: 12px 14px;">Payment</th>
                    <th style="padding: 12px 14px;">Tracking ID</th>
                    <th style="padding: 12px 14px;">Order Status</th>
                    <th style="padding: 12px 18px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="padding: 30px; text-align: center; color: var(--text-muted);">
                            No orders found matching the selected filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $ord): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px; font-weight: 700;">
                                <a href="<?= url('order/view', ['id' => $ord['id']]) ?>" class="notranslate" translate="no"><?= e($ord['order_number']) ?></a>
                            </td>
                            <td style="padding: 12px 14px; color: var(--text-muted);">
                                <?= date('M d, H:i', strtotime($ord['created_at'])) ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <strong><?= e($ord['customer_name']) ?></strong><br>
                                <span style="font-size: 11px; color: var(--text-muted);"><?= e($ord['address_snapshot']['city'] ?? '') ?>, <?= e($ord['address_snapshot']['country'] ?? '') ?></span>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 700;"><?= money($ord['total_amount']) ?></td>
                            <td style="padding: 12px 14px;">
                                <?= status_badge($ord['payment_status']) ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <?php if (!empty($ord['tracking_number'])): ?>
                                    <a href="<?= url('tracking/track', ['code' => $ord['tracking_number']]) ?>" class="notranslate" translate="no" style="font-weight: 600;">
                                        <?= e($ord['tracking_number']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">Not packed</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <?= status_badge($ord['order_status']) ?>
                            </td>
                            <td style="padding: 12px 18px; text-align: right;">
                                <?php if (empty($ord['tracking_number']) && $ord['payment_status'] === 'paid' && !in_array($ord['order_status'], ['cancelled', 'returned'], true)): ?>
                                    <form action="<?= url('admin/order-pack') ?>" method="POST" style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                        <select name="courier_id" class="form-control" style="width: auto; padding: 4px 8px; font-size: 11px;">
                                            <?php foreach ($couriers as $cr): ?>
                                                <option value="<?= $cr['id'] ?>"><?= e($cr['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary" style="font-size: 12px; padding: 4px 10px;">
                                            Pack & Track
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?= url('order/view', ['id' => $ord['id']]) ?>" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 8px;">
                                        Details
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
