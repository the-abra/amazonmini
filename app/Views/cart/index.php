<?php
// app/Views/cart/index.php
?>
<div style="max-width: 1040px; margin: 20px auto;">
    <!-- Checkout Funnel Stepper -->
    <div class="funnel-steps">
        <div class="funnel-step active">
            <span class="funnel-step-badge">1</span>
            <span>Shopping Cart</span>
        </div>
        <div class="funnel-divider"></div>
        <div class="funnel-step">
            <span class="funnel-step-badge">2</span>
            <span>Shipping Address</span>
        </div>
        <div class="funnel-divider"></div>
        <div class="funnel-step">
            <span class="funnel-step-badge">3</span>
            <span>Stripe Payment</span>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h1 style="font-size: 26px; font-weight: 800; letter-spacing: -0.5px;">Shopping Cart</h1>
        <a href="<?= url('home') ?>" style="font-size: 13px; font-weight: 600;">&larr; Continue Shopping</a>
    </div>

    <?php if (empty($summary['items'])): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <svg style="margin: 0 auto 16px auto; color: var(--text-muted);" width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 8px;">Your Shopping Cart is empty</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">Explore our catalog to find electronics, audio gear, accessories, and books.</p>
            <a href="<?= url('home') ?>" class="btn btn-primary btn-lg">Explore Store Catalog</a>
        </div>
    <?php else: ?>
        <?php
            $subtotal = (float)$summary['subtotal'];
            $threshold = 50.00;
            $percent = min(100, (int)round(($subtotal / $threshold) * 100));
            $remaining = max(0, $threshold - $subtotal);
        ?>
        <!-- Free Shipping Progress Meter -->
        <div class="shipping-meter-card">
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 700;">
                <?php if ($remaining <= 0): ?>
                    <span style="color: var(--accent); display: flex; align-items: center; gap: 6px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Congratulations! You have unlocked FREE Express Shipping!
                    </span>
                    <span style="color: var(--accent); font-weight: 800;">100%</span>
                <?php else: ?>
                    <span style="color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                        Add <strong>$<?= number_format($remaining, 2) ?></strong> more to qualify for <strong>FREE Shipping</strong>
                    </span>
                    <span style="color: var(--primary); font-weight: 800;"><?= $percent ?>%</span>
                <?php endif; ?>
            </div>
            <div class="meter-track">
                <div class="meter-fill" style="width: <?= $percent ?>%;"></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1.1fr; gap: 24px; align-items: start;">
            <!-- Cart Items List -->
            <div class="card" style="padding: 0; overflow: hidden;">
                <?php foreach ($summary['items'] as $item): ?>
                    <div style="display: flex; gap: 18px; padding: 20px; border-bottom: 1px solid var(--border-color); align-items: center;">
                        <a href="<?= url('product/view', ['id' => $item['id']]) ?>" style="width: 88px; height: 88px; flex-shrink: 0; background: var(--bg-subtle); border-radius: var(--radius-md); overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>" style="width: 100%; height: 100%; object-fit: contain;">
                        </a>

                        <div style="flex: 1;">
                            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 700; margin-bottom: 2px;"><?= e($item['category_name']) ?></div>
                            <h3 style="font-size: 16px; font-weight: 700; line-height: 1.35; margin-bottom: 6px;">
                                <a href="<?= url('product/view', ['id' => $item['id']]) ?>" style="color: inherit;"><?= e($item['name']) ?></a>
                            </h3>
                            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">
                                Unit Price: <strong style="color: var(--text-primary);"><?= money($item['price']) ?></strong> &bull; 
                                <span style="color: var(--accent); font-weight: 600;">In Stock (<?= (int)$item['stock'] ?>)</span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 14px;">
                                <form action="<?= url('cart/update') ?>" method="POST" style="display: flex; align-items: center; gap: 6px;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <label style="font-size: 12px; font-weight: 600; color: var(--text-muted);">Qty:</label>
                                    <select name="quantity" class="form-control" style="width: 72px; padding: 5px 8px; font-size: 13px; font-weight: 700;" onchange="this.form.submit()">
                                        <?php for ($q = 1; $q <= min(10, (int)$item['stock']); $q++): ?>
                                            <option value="<?= $q ?>" <?= ($q == $item['quantity']) ? 'selected' : '' ?>><?= $q ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </form>

                                <form action="<?= url('cart/remove') ?>" method="POST" style="display: inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 12px; color: var(--danger); border-color: var(--border-color); padding: 5px 10px;">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div style="text-align: right; font-weight: 800; font-size: 18px; min-width: 90px; color: var(--text-primary);">
                            <?= money($item['line_total']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary Sidebar -->
            <div>
                <div class="card" style="position: sticky; top: 120px;">
                    <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 18px; letter-spacing: -0.3px;">Order Summary</h3>
                    
                    <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 12px; color: var(--text-secondary);">
                        <span>Items Subtotal (<?= $summary['count'] ?>):</span>
                        <strong style="color: var(--text-primary);"><?= money($summary['subtotal']) ?></strong>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 12px; color: var(--text-secondary);">
                        <span>Estimated Shipping:</span>
                        <span><?= ($summary['shipping'] > 0) ? money($summary['shipping']) : '<span style="color: var(--accent); font-weight: 700;">FREE</span>' ?></span>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 14px; margin-top: 10px; display: flex; justify-content: space-between; font-size: 20px; font-weight: 800; color: var(--text-primary);">
                        <span>Estimated Total:</span>
                        <span><?= money($summary['total']) ?></span>
                    </div>

                    <div style="margin-top: 22px;">
                        <a href="<?= url('checkout') ?>" class="btn btn-primary btn-lg btn-block" style="padding: 14px; font-size: 16px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            Proceed to Checkout
                        </a>
                    </div>

                    <div style="margin-top: 14px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; color: var(--text-muted);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>256-bit SSL Encrypted Sandbox Checkout</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
