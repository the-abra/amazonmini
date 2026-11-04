<?php
// app/Views/admin/webhooks.php
?>
<div style="margin-bottom: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Stripe Webhook Console</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Asynchronous payment notifications: signed, idempotent and fully audited.</p>
        </div>
        <a href="<?= url('admin/dashboard') ?>" class="btn btn-secondary">Dashboard</a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 24px; align-items: start;">
        <div>
            <div class="card">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">Simulate a Stripe Event</h3>
                <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 16px;">
                    Builds a realistic event and runs it through the exact handler used by the real endpoint.
                </p>
                <form action="<?= url('admin/webhook-simulate') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">Order</label>
                        <select name="order_id" class="form-control" required>
                            <?php foreach ($orders as $o): ?>
                                <option value="<?= (int)$o['id'] ?>"><?= e($o['order_number']) ?> &mdash; <?= e($o['payment_status']) ?> / <?= e($o['order_status']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Event type</label>
                        <select name="event_type" class="form-control" required>
                            <?php foreach ($eventTypes as $t): ?>
                                <option value="<?= e($t) ?>"><?= e($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Send Simulated Event</button>
                </form>
            </div>

            <div class="card">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 10px;">Real endpoint</h3>
                <code class="mono" style="display: block; background: var(--bg-subtle); padding: 10px; border-radius: var(--radius-sm); word-break: break-all;">POST <?= e($appUrl) ?>/index.php?route=webhook/stripe</code>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 10px; line-height: 1.6;">
                    Verifies the <span class="mono">Stripe-Signature</span> header (HMAC-SHA256, 5 min tolerance),
                    stores every <span class="mono">event.id</span> once (replays are acknowledged and ignored) and returns
                    <span class="mono">5xx</span> on handler failure so Stripe retries.<br>
                    Try it from a terminal: <span class="mono">make webhook EVENT=checkout.session.completed ORDER=3</span>
                </p>
            </div>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); font-weight: 700;">Recent Events</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: var(--bg-subtle); text-align: left; border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 10px 16px;">Time</th>
                        <th style="padding: 10px 12px;">Event</th>
                        <th style="padding: 10px 12px;">Order</th>
                        <th style="padding: 10px 12px;">Outcome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="4" style="padding: 30px; text-align: center; color: var(--text-muted);">No webhook events received yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($events as $ev): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); vertical-align: top;">
                            <td style="padding: 10px 16px; white-space: nowrap; color: var(--text-muted);"><?= date('M d, H:i:s', strtotime($ev['created_at'])) ?></td>
                            <td style="padding: 10px 12px;">
                                <span class="mono notranslate" translate="no"><?= e($ev['event_type']) ?></span><br>
                                <span class="mono" style="color: var(--text-muted);"><?= e($ev['event_id']) ?></span>
                                <?= $ev['source'] === 'simulated' ? status_badge('pending', 'simulated') : '' ?>
                            </td>
                            <td style="padding: 10px 12px;"><?= $ev['order_id'] ? '#' . (int)$ev['order_id'] : '&mdash;' ?></td>
                            <td style="padding: 10px 12px; color: var(--text-secondary);"><?= e($ev['outcome']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
