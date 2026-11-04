<?php
// app/Views/checkout/index.php
?>
<div style="max-width: 1040px; margin: 20px auto;">
    <!-- Checkout Funnel Stepper -->
    <div class="funnel-steps">
        <div class="funnel-step completed">
            <span class="funnel-step-badge">✓</span>
            <span>Shopping Cart</span>
        </div>
        <div class="funnel-divider"></div>
        <div class="funnel-step active">
            <span class="funnel-step-badge">2</span>
            <span>Delivery & Review</span>
        </div>
        <div class="funnel-divider"></div>
        <div class="funnel-step">
            <span class="funnel-step-badge">3</span>
            <span>Stripe Payment</span>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 22px;">
        <h1 style="font-size: 26px; font-weight: 800; letter-spacing: -0.5px;">Review & Place Order</h1>
        <a href="<?= url('cart') ?>" style="font-size: 13px; font-weight: 600;">&larr; Return to Cart</a>
    </div>

    <!-- Educational Stripe Sandbox Notice -->
    <div style="background: var(--primary-light); border: 1px solid #c7d2fe; border-radius: var(--radius-md); padding: 16px 20px; margin-bottom: 24px; box-shadow: var(--shadow-xs);">
        <div style="display: flex; align-items: flex-start; gap: 14px;">
            <div style="width: 36px; height: 36px; border-radius: var(--radius-md); background: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: var(--shadow-xs);">
                <svg style="color: var(--primary);" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </div>
            <div>
                <strong style="color: #3730a3; font-size: 14px;">Stripe Test Sandbox Environment</strong>
                <p style="color: #4338ca; font-size: 13px; margin: 4px 0 0 0; line-height: 1.5;">
                    This checkout is connected to your active Stripe test sandbox. You can use standard test card <code class="notranslate" translate="no" style="background: white; padding: 2px 6px; border-radius: 4px; font-weight: 700; border: 1px solid #c7d2fe;">4242 4242 4242 4242</code> with any future expiration date and 3-digit CVC.
                    <?php if (!$stripeReady): ?>
                        <?php if (!empty($demoPayments)): ?>
                            <br><strong style="color: var(--accent);">Demo gateway active:</strong> no Stripe key configured, so clicking pay completes the order instantly with a simulated payment.
                        <?php else: ?>
                            <br><strong style="color: var(--danger);">Notice:</strong> No Stripe Secret Key found in configuration. Add your key to <code class="notranslate" translate="no">config/config.local.php</code>.
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <form action="<?= url('checkout/create') ?>" method="POST">
        <?= csrf_field() ?>

        <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 24px; align-items: start;">
            <!-- Left: Shipping Address Selection -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2 style="font-size: 18px; font-weight: 700;">1. Select Shipping Address</h2>
                        <a href="<?= url('profile') ?>" class="btn btn-sm btn-secondary">+ Manage Addresses</a>
                    </div>

                    <?php if (empty($addresses)): ?>
                        <div style="padding: 16px; background: var(--warning-light); border: 1px solid #fde68a; border-radius: var(--radius-sm); font-size: 14px; color: #92400e;">
                            You have no saved addresses. Please <a href="<?= url('profile') ?>" style="font-weight: 700; text-decoration: underline;">add an address to your address book</a> first.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($addresses as $addr): ?>
                                <label style="display: flex; gap: 14px; padding: 16px; border: 1.5px solid <?= $addr['is_default'] ? 'var(--primary)' : 'var(--border-color)' ?>; border-radius: var(--radius-md); cursor: pointer; transition: var(--transition-fast); background: var(--bg-surface);">
                                    <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $addr['is_default'] ? 'checked' : '' ?> style="margin-top: 4px; accent-color: var(--primary); transform: scale(1.15);">
                                    <div style="flex: 1;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                            <strong style="font-size: 15px; color: var(--text-primary);">
                                                <?= e($addr['title']) ?> &bull; <?= e($addr['recipient_name']) ?>
                                            </strong>
                                            <?php if ($addr['is_default']): ?>
                                                <span class="badge-count" style="background: var(--accent); font-size: 11px;">Default Address</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 13px; color: var(--text-secondary); line-height: 1.45;">
                                            <?= e($addr['address_line']) ?>, <?= e($addr['city']) ?>, <?= e($addr['postal_code']) ?> (<?= e($addr['country']) ?>)<br>
                                            <span style="color: var(--text-muted);">Contact Phone: <?= e($addr['phone']) ?></span>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Shipping Method Note -->
                <div class="card">
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 8px;">2. Shipping Method & Cargo Tracking</h3>
                    <div style="display: flex; gap: 12px; align-items: center; background: var(--bg-subtle); padding: 14px; border-radius: var(--radius-md);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary); flex-shrink: 0;"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                        <div style="font-size: 13px; color: var(--text-secondary);">
                            <strong>Amazon Express Courier</strong> &bull; Estimated Delivery: <strong style="color: var(--text-primary);"><?= date('l, M d', strtotime('+3 days')) ?></strong><br>
                            Includes real-time facility checkpoint logs and courier assignment.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Order Review & Stripe Pay Button -->
            <div>
                <div class="card" style="position: sticky; top: 120px;">
                    <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 16px;">Order Summary</h3>

                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 18px; max-height: 220px; overflow-y: auto; padding-right: 4px;">
                        <?php foreach ($summary['items'] as $item): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; gap: 12px; border-bottom: 1px solid var(--bg-subtle); padding-bottom: 8px;">
                                <div>
                                    <span style="font-weight: 600; color: var(--text-primary);"><?= e($item['name']) ?></span>
                                    <span style="color: var(--text-muted); font-size: 12px;"> &times; <?= $item['quantity'] ?></span>
                                </div>
                                <span style="font-weight: 700;"><?= money($item['line_total']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 14px; margin-bottom: 18px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: var(--text-secondary);">
                            <span>Items Subtotal:</span>
                            <strong><?= money($summary['subtotal']) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: var(--text-secondary);">
                            <span>Express Shipping:</span>
                            <span><?= ($summary['shipping'] > 0) ? money($summary['shipping']) : '<span style="color: var(--accent); font-weight: 700;">FREE</span>' ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 800; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 8px; color: var(--text-primary);">
                            <span>Total Due:</span>
                            <span><?= money($summary['total']) ?></span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-accent btn-lg btn-block" style="padding: 15px; font-size: 16px; box-shadow: 0 4px 14px 0 rgba(5, 150, 105, 0.35);" <?= empty($addresses) ? 'disabled' : '' ?>>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Pay <?= money($summary['total']) ?> with Stripe
                    </button>

                    <div style="text-align: center; margin-top: 14px; font-size: 12px; color: var(--text-muted); line-height: 1.4;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 3px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Redirects securely to Stripe's hosted Checkout sandbox.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
