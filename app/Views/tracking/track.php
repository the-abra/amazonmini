<?php
// app/Views/tracking/track.php
?>
<div style="max-width: 960px; margin: 20px auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">Amazon Logistics & Cargo Tracking</h1>
        <p style="color: var(--text-muted); font-size: 15px;">Enter your tracking number to monitor package dispatch, hub sorting, and doorstep delivery.</p>

        <!-- Tracking Search Form -->
        <form action="index.php" method="GET" style="max-width: 500px; margin: 20px auto 10px auto; display: flex; gap: 8px;">
            <input type="hidden" name="route" value="tracking/track">
            <input type="text" name="code" class="form-control" placeholder="e.g. TRK-2026-88001" value="<?= e($code) ?>" required style="text-align: center; font-weight: 600; letter-spacing: 0.5px;">
            <button type="submit" class="btn btn-primary" style="padding: 0 24px;">Track</button>
        </form>

        <!-- Quick Demo Tracking IDs -->
        <div style="font-size: 12px; color: var(--text-muted);">
            Sample demo tracking numbers:
            <a href="<?= url('tracking/track', ['code' => 'TRK-2026-88001']) ?>" class="notranslate" translate="no" style="font-weight: 600;">TRK-2026-88001 (Delivered)</a> &bull;
            <a href="<?= url('tracking/track', ['code' => 'TRK-2026-88002']) ?>" class="notranslate" translate="no" style="font-weight: 600;">TRK-2026-88002 (In Transit)</a> &bull;
            <a href="<?= url('tracking/track', ['code' => 'TRK-2026-88003']) ?>" class="notranslate" translate="no" style="font-weight: 600;">TRK-2026-88003 (Packed)</a> &bull;
            <a href="<?= url('tracking/track', ['code' => 'TRK-2026-88004']) ?>" class="notranslate" translate="no" style="font-weight: 600; color: var(--danger);">TRK-2026-88004 (Delivery Failed)</a>
        </div>
    </div>

    <?php if ($shipment): ?>
        <?php 
            $currentStatusKey = $shipment['current_status'];
            $isException = isset($allStatuses[$currentStatusKey]) && !isset($statuses[$currentStatusKey]);
            $currentStepNum = $statuses[$currentStatusKey]['step'] ?? 4;
            if ($currentStatusKey === 'cancelled') {
                $currentStepNum = 1;
            }
        ?>
        <!-- Cargo Tracker Progress Card -->
        <div class="cargo-tracker-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px;">
                <div>
                    <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Tracking Number</span>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 2px;">
                        <h2 class="notranslate" translate="no" style="font-size: 24px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px;">
                            <?= e($shipment['tracking_number']) ?>
                        </h2>
                        <button type="button" class="btn btn-sm btn-secondary" data-action="copy-tracking" data-code="<?= e($shipment['tracking_number']) ?>" style="padding: 4px 10px; font-size: 12px; gap: 4px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                            <span>Copy</span>
                        </button>
                    </div>
                    <div style="font-size: 13px; color: var(--text-secondary); margin-top: 6px;">
                        Carrier: <strong><?= e($shipment['carrier_name']) ?></strong> &bull;
                        Order: <a href="<?= url('order/view', ['id' => $shipment['order_id']]) ?>" class="notranslate" translate="no" style="font-weight: 700;">#<?= e($shipment['order_number']) ?></a>
                    </div>
                </div>

                <div style="text-align: right;">
                    <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Estimated Delivery</span>
                    <div style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin-top: 2px;">
                        <?= $shipment['estimated_delivery'] ? date('l, M d, Y', strtotime($shipment['estimated_delivery'])) : 'Pending Dispatch' ?>
                    </div>
                    <div style="font-size: 13px; color: var(--accent); font-weight: 700; margin-top: 3px; display: flex; align-items: center; justify-content: flex-end; gap: 5px;">
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--accent);"></span>
                        <?= e($allStatuses[$currentStatusKey]['label'] ?? $currentStatusKey) ?>
                    </div>
                </div>
            </div>

            <?php if ($isException): ?>
                <div class="tracker-exception">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        <strong><?= e($allStatuses[$currentStatusKey]['label']) ?></strong><br>
                        <?= e($allStatuses[$currentStatusKey]['description']) ?>
                        <?php if ((int)$shipment['delivery_attempts'] > 0): ?>
                            <br>Delivery attempts:
                            <span class="attempt-dots"><?php for ($i = 1; $i <= \App\Models\Shipment::MAX_DELIVERY_ATTEMPTS; $i++): ?><i class="<?= $i <= (int)$shipment['delivery_attempts'] ? 'used' : '' ?>"></i><?php endfor; ?></span>
                            <?= (int)$shipment['delivery_attempts'] ?>/<?= \App\Models\Shipment::MAX_DELIVERY_ATTEMPTS ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Horizontal Step Indicator with Progress Fill Line -->
            <?php
                $stepFills = [1 => '0%', 2 => '25%', 3 => '50%', 4 => '75%', 5 => '100%'];
                $fillWidth = $stepFills[$currentStepNum] ?? '0%';
            ?>
            <div class="tracker-steps">
                <div class="tracker-line-bg"></div>
                <div class="tracker-line-fill" style="width: calc((100% - 80px) * <?= ($currentStepNum - 1) / 4 ?>);"></div>

                <?php foreach ($statuses as $key => $meta): ?>
                    <?php 
                        $stepClass = '';
                        if ($meta['step'] < $currentStepNum) {
                            $stepClass = 'completed';
                        } elseif ($meta['step'] === $currentStepNum) {
                            $stepClass = ($key === 'delivered') ? 'completed active' : 'active';
                            if ($currentStatusKey === 'delivery_failed') {
                                $stepClass = 'failed';
                            }
                        }
                        if ($isException && $currentStatusKey !== 'delivery_failed' && $meta['step'] >= $currentStepNum && $currentStatusKey !== 'cancelled') {
                            $stepClass = 'failed';
                        }
                    ?>
                    <div class="tracker-step <?= $stepClass ?>">
                        <div class="step-dot" title="<?= e($meta['label']) ?>">
                            <?php if ($meta['step'] < $currentStepNum || ($meta['step'] === $currentStepNum && $key === 'delivered')): ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?php elseif ($stepClass === 'failed'): ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <?php elseif ($key === 'order_placed'): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                            <?php elseif ($key === 'picked_up'): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <?php elseif ($key === 'in_transit'): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                            <?php elseif ($key === 'out_for_delivery'): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            <?php else: ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            <?php endif; ?>
                        </div>
                        <div class="step-label"><?= e($meta['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Destination summary -->
            <?php if (!empty($shipment['address_snapshot'])): ?>
                <div style="background: var(--bg-subtle); padding: 14px 20px; border-radius: var(--radius-md); font-size: 13px; color: var(--text-secondary); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <strong>Delivery Destination:</strong> <?= e($shipment['address_snapshot']['city'] ?? '') ?>, <?= e($shipment['address_snapshot']['postal_code'] ?? '') ?> (<?= e($shipment['address_snapshot']['recipient_name'] ?? '') ?>) &bull; <?= e($shipment['address_snapshot']['country'] ?? '') ?>
                    </div>
                    <?php if (!empty($shipment['courier_name'])): ?>
                        <div>
                            <strong>Assigned Logistics Driver:</strong> <?= e($shipment['courier_name']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Checkpoint History Table -->
        <div class="card">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">Shipment Transit & Scan History</h3>

            <?php if (empty($shipment['events'])): ?>
                <p style="color: var(--text-muted); font-size: 14px;">No checkpoint updates scanned yet.</p>
            <?php else: ?>
                <table class="timeline-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Checkpoint Stage</th>
                            <th>Facility / Hub Location</th>
                            <th>Details & Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shipment['events'] as $event): ?>
                            <tr>
                                <td style="white-space: nowrap; font-size: 13px; font-weight: 500;">
                                    <?= date('M d, Y &bull; H:i', strtotime($event['event_time'])) ?>
                                </td>
                                <td>
                                    <?= status_badge($event['status_step'], $allStatuses[$event['status_step']]['label'] ?? null) ?>
                                </td>
                                <td style="font-weight: 500;">
                                    <?= e($event['location']) ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 13px;">
                                    <?= e($event['notes']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php elseif ($code !== ''): ?>
        <div class="card" style="text-align: center; padding: 40px 20px;">
            <svg style="margin: 0 auto 16px auto; color: var(--warning);" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <h3 style="font-size: 18px; margin-bottom: 8px;">Tracking Number Not Found</h3>
            <p style="color: var(--text-secondary); font-size: 14px; max-width: 500px; margin: 0 auto 20px auto;">
                Could not find any package record with tracking code "<strong><?= e($code) ?></strong>".
                Please check the spelling or try one of our pre-seeded demo codes above.
            </p>
            <a href="<?= url('tracking/track', ['code' => 'TRK-2026-88001']) ?>" class="btn btn-secondary">
                View Sample Delivered Package
            </a>
        </div>
    <?php endif; ?>
</div>
