<?php
// app/Views/auth/profile.php
?>
<div style="max-width: 900px; margin: 20px auto;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Account Settings</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Manage your profile information and shipping address book.</p>
        </div>
        <a href="<?= url('order/list') ?>" class="btn btn-secondary">
            View My Orders
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <!-- User Profile Card -->
        <div class="card">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px;">Personal Info</h3>
            <div style="font-size: 14px; margin-bottom: 10px;">
                <span style="color: var(--text-muted); display: block; font-size: 12px;">Full Name</span>
                <strong><?= e($user['name']) ?></strong>
            </div>
            <div style="font-size: 14px; margin-bottom: 10px;">
                <span style="color: var(--text-muted); display: block; font-size: 12px;">Email Address</span>
                <span><?= e($user['email']) ?></span>
            </div>
            <div style="font-size: 14px; margin-bottom: 10px;">
                <span style="color: var(--text-muted); display: block; font-size: 12px;">Account Role</span>
                <span class="logo-badge" style="display: inline-block; text-transform: uppercase;"><?= e($user['role']) ?></span>
            </div>
            <div style="font-size: 14px;">
                <span style="color: var(--text-muted); display: block; font-size: 12px;">Registered Since</span>
                <span><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
            </div>
        </div>

        <!-- Address Book -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 style="font-size: 16px; font-weight: 700;">Shipping Addresses</h3>
                    <button type="button" class="btn btn-sm btn-primary" data-modal-open="add-address-modal">
                        + Add Address
                    </button>
                </div>

                <?php if (empty($addresses)): ?>
                    <p style="color: var(--text-muted); font-size: 14px; padding: 10px 0;">No saved shipping addresses yet. Add one to enable seamless checkout.</p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;">
                        <?php foreach ($addresses as $addr): ?>
                            <div style="border: 1px solid <?= $addr['is_default'] ? 'var(--primary)' : 'var(--border-color)' ?>; border-radius: var(--radius-md); padding: 16px; background: var(--bg-surface); position: relative;">
                                <?php if ($addr['is_default']): ?>
                                    <span class="badge-count" style="position: absolute; top: 12px; right: 12px; font-size: 11px; background: var(--accent);">Default</span>
                                <?php endif; ?>

                                <strong style="font-size: 14px; display: block; margin-bottom: 4px;"><?= e($addr['title']) ?></strong>
                                <div style="font-size: 13px; color: var(--text-secondary); line-height: 1.4; margin-bottom: 12px;">
                                    <div><strong><?= e($addr['recipient_name']) ?></strong></div>
                                    <div><?= e($addr['address_line']) ?></div>
                                    <div><?= e($addr['city']) ?>, <?= e($addr['postal_code']) ?></div>
                                    <div><?= e($addr['country']) ?></div>
                                    <div style="margin-top: 4px; color: var(--text-muted);"><?= e($addr['phone']) ?></div>
                                </div>

                                <div style="display: flex; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                                    <?php if (!$addr['is_default']): ?>
                                        <form action="<?= url('profile/address-default') ?>" method="POST" style="display: inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 8px;">Set Default</button>
                                        </form>
                                    <?php endif; ?>

                                    <form action="<?= url('profile/address-delete') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Remove this address?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 8px; color: var(--danger);">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div id="add-address-modal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 18px; font-weight: 700;">Add New Shipping Address</h2>
            <button type="button" data-modal-close="add-address-modal" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>

        <form action="<?= url('profile/address-add') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label">Address Title / Label</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Home, Office, Dorm" value="Home" required>
            </div>

            <div class="form-group">
                <label class="form-label">Recipient Full Name</label>
                <input type="text" name="recipient_name" class="form-control" required placeholder="Recipient name" value="<?= e($user['name']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" required placeholder="+1 555-0100" value="<?= e($user['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Street Address & Apartment / Suite</label>
                <input type="text" name="address_line" class="form-control" required placeholder="123 Main Street, Suite 4B">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" required placeholder="Seattle">
                </div>
                <div class="form-group">
                    <label class="form-label">Postal / ZIP Code</label>
                    <input type="text" name="postal_code" class="form-control" required placeholder="98101">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Country</label>
                <input type="text" name="country" class="form-control" value="United States" required>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="addr-default-check" name="is_default" value="1" checked>
                <label for="addr-default-check" style="font-size: 13px; cursor: pointer;">Set as default shipping address</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" data-modal-close="add-address-modal" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Address</button>
            </div>
        </form>
    </div>
</div>
