<?php
// app/Views/courier/dashboard.php
?>
<div style="margin-bottom: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Logistics Officer Dispatch Portal</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Scan packages, log hub checkpoint arrivals, and confirm doorstep deliveries.</p>
        </div>
        <div>
            <a href="<?= url('tracking/track') ?>" class="btn btn-secondary">Public Tracker</a>
        </div>
    </div>

    <!-- 2-Column Courier Workspace -->
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
        <!-- Left: Active Shipments List -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 16px; font-weight: 700;">Shipment Route Assignments</h3>
                <span style="font-size: 12px; color: var(--text-muted);"><?= count($shipments) ?> packages in queue</span>
            </div>

            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: var(--bg-subtle); text-align: left; border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px;">Tracking ID</th>
                        <th style="padding: 12px 14px;">Recipient</th>
                        <th style="padding: 12px 14px;">Current Status</th>
                        <th style="padding: 12px 16px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shipments)): ?>
                        <tr>
                            <td colspan="4" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                No shipments currently assigned.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($shipments as $sh): ?>
                            <tr style="border-bottom: 1px solid var(--border-color); <?= ($selectedShipment && $selectedShipment['id'] == $sh['id']) ? 'background: var(--primary-light);' : '' ?>">
                                <td style="padding: 12px 16px; font-weight: 700;">
                                    <span class="notranslate" translate="no"><?= e($sh['tracking_number']) ?></span>
                                    <div style="font-size: 11px; color: var(--text-muted); font-weight: normal;">
                                        Order #<?= e($sh['order_number']) ?>
                                    </div>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <?= e($sh['customer_name']) ?>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <?= status_badge($sh['current_status'], $statuses[$sh['current_status']]['label'] ?? null) ?>
                                    <?php if ((int)$sh['delivery_attempts'] > 0): ?>
                                        <div class="attempt-dots" title="<?= (int)$sh['delivery_attempts'] ?> failed attempt(s)" style="margin-top: 6px;">
                                            <?php for ($i = 1; $i <= $maxAttempts; $i++): ?><i class="<?= $i <= (int)$sh['delivery_attempts'] ? 'used' : '' ?>"></i><?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: right;">
                                    <?php if (\App\Models\Shipment::allowedNext($sh['current_status'])): ?>
                                        <a href="<?= url('courier/dashboard', ['track' => $sh['tracking_number']]) ?>" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 8px;">
                                            Update Scan
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--text-muted);">Closed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Right: Checkpoint Scan Form -->
        <div>
            <div class="card">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 14px;">Log Physical Checkpoint</h3>
                <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 18px;">
                    Select an active parcel to record the next logistics step into the permanent audit trail.
                </p>

                <?php if ($selectedShipment && empty($nextStatuses)): ?>
                    <div class="banner banner-info" style="margin-bottom: 0;">
                        <div>
                            <strong><?= e($selectedShipment['tracking_number']) ?> is closed.</strong>
                            Final state: <?= e($statuses[$selectedShipment['current_status']]['label'] ?? $selectedShipment['current_status']) ?>. No further checkpoints can be logged.
                        </div>
                    </div>
                <?php elseif ($selectedShipment): ?>
                    <form action="<?= url('courier/update-status') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="shipment_id" value="<?= $selectedShipment['id'] ?>">

                        <div style="background: var(--bg-subtle); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px;">
                            <div>Tracking: <strong class="notranslate" translate="no"><?= e($selectedShipment['tracking_number']) ?></strong></div>
                            <div>Recipient: <strong><?= e($selectedShipment['customer_name']) ?></strong></div>
                            <div>Destination: <?= e($selectedShipment['address_snapshot']['address_line'] ?? '') ?>, <?= e($selectedShipment['address_snapshot']['city'] ?? '') ?> <?= e($selectedShipment['address_snapshot']['postal_code'] ?? '') ?></div>
                            <div>Current Stage: <strong style="color: var(--primary);"><?= e($statuses[$selectedShipment['current_status']]['label'] ?? $selectedShipment['current_status']) ?></strong></div>
                            <div>Failed attempts:
                                <span class="attempt-dots"><?php for ($i = 1; $i <= $maxAttempts; $i++): ?><i class="<?= $i <= (int)$selectedShipment['delivery_attempts'] ? 'used' : '' ?>"></i><?php endfor; ?></span>
                                <?= (int)$selectedShipment['delivery_attempts'] ?>/<?= $maxAttempts ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Next Checkpoint Status</label>
                            <select name="status" id="courier-status" class="form-control" required>
                                <?php foreach ($nextStatuses as $stKey): ?>
                                    <option value="<?= $stKey ?>"><?= $stKey === 'delivery_failed' ? 'Could not deliver / recipient absent' : e($statuses[$stKey]['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" id="failure-reason-group" style="display: none;">
                            <label class="form-label">Failure Reason</label>
                            <select name="reason_code" id="courier-reason" class="form-control">
                                <?php foreach ($failureReasons as $code => $text): ?>
                                    <option value="<?= e($code) ?>"><?= e($text) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div style="font-size: 12px; color: var(--danger); margin-top: 6px;">
                                Attempt <?= (int)$selectedShipment['delivery_attempts'] + 1 ?> of <?= $maxAttempts ?>.
                                <?= ((int)$selectedShipment['delivery_attempts'] + 1 >= $maxAttempts) ? 'This is the final attempt: the parcel will be returned to sender and the customer refunded.' : 'The customer will be notified and a retry scheduled.' ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Facility / Hub Physical Location</label>
                            <input type="text" name="location" class="form-control" required placeholder="e.g. Regional Hub #4, Kent WA" value="<?= e($selectedShipment['address_snapshot']['city'] ?? 'Regional Sorting Hub, Kent WA') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Logistics Scan Notes</label>
                            <textarea name="notes" rows="3" class="form-control" required placeholder="Describe handling checkpoint...">Package processed through automated optical scanner.</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="padding: 12px;">
                            Confirm Checkpoint Scan
                        </button>
                    </form>
                    <script>
                        (function () {
                            var status = document.getElementById('courier-status');
                            var group = document.getElementById('failure-reason-group');
                            function sync() { group.style.display = status.value === 'delivery_failed' ? 'block' : 'none'; }
                            status.addEventListener('change', sync);
                            sync();
                        })();
                    </script>
                <?php else: ?>
                    <div style="text-align: center; padding: 30px 10px; color: var(--text-muted); font-size: 14px;">
                        Click <strong>Update Scan</strong> on any parcel on the left to log its transit movement.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
