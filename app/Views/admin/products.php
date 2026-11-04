<?php
// app/Views/admin/products.php
?>
<div style="margin-bottom: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Catalog & Inventory Management</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Add new catalog items, adjust inventory, and configure product specifications.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-primary" data-modal-open="product-modal">
                + Add New Product
            </button>
            <a href="<?= url('admin/dashboard') ?>" class="btn btn-secondary">Dashboard</a>
        </div>
    </div>

    <!-- Products Table Card -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: var(--bg-subtle); text-align: left; border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 18px;">Product</th>
                    <th style="padding: 12px 14px;">Category</th>
                    <th style="padding: 12px 14px;">SKU</th>
                    <th style="padding: 12px 14px; text-align: right;">Price</th>
                    <th style="padding: 12px 14px; text-align: center;">Stock</th>
                    <th style="padding: 12px 14px; text-align: center;">Status</th>
                    <th style="padding: 12px 18px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 18px; display: flex; align-items: center; gap: 12px;">
                            <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                            <div>
                                <a href="<?= url('product/view', ['id' => $p['id']]) ?>" style="font-weight: 600; color: inherit;">
                                    <?= e($p['name']) ?>
                                </a>
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    Rating: <?= number_format((float)$p['rating_avg'], 1) ?> ★ (<?= $p['review_count'] ?> reviews)
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px 14px;"><?= e($p['category_name']) ?></td>
                        <td style="padding: 12px 14px; font-weight: 500;" class="notranslate" translate="no"><?= e($p['sku']) ?></td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 700;"><?= money($p['price']) ?></td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <span style="font-weight: 600; color: <?= ($p['stock'] < 10) ? 'var(--danger)' : 'var(--accent)' ?>;">
                                <?= (int)$p['stock'] ?>
                            </span>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <span class="badge-count" style="background: <?= $p['is_active'] ? 'var(--accent)' : 'var(--danger)' ?>;">
                                <?= $p['is_active'] ? 'ACTIVE' : 'INACTIVE' ?>
                            </span>
                        </td>
                        <td style="padding: 12px 18px; text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                <a href="<?= url('admin/products', ['edit_id' => $p['id']]) ?>" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 8px;">
                                    Edit
                                </a>
                                <form action="<?= url('admin/product-delete') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Permanently remove product?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" style="font-size: 12px; padding: 4px 8px; color: var(--danger);">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Product Modal -->
<div id="product-modal" class="modal-backdrop <?= $editProduct ? 'is-open' : '' ?>" aria-hidden="<?= $editProduct ? 'false' : 'true' ?>">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 18px; font-weight: 700;"><?= $editProduct ? 'Edit Product' : 'Add New Product' ?></h2>
            <button type="button" data-modal-close="product-modal" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>

        <form action="<?= url('admin/product-save') ?>" method="POST">
            <?= csrf_field() ?>
            <?php if ($editProduct): ?>
                <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($editProduct['name'] ?? '') ?>" placeholder="e.g. Mechanical Keyboard Pro">
                </div>
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" required value="<?= e($editProduct['sku'] ?? '') ?>" placeholder="e.g. KEY-PRO-01">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (($editProduct['category_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Price ($ USD)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required value="<?= e($editProduct['price'] ?? '49.99') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Stock Quantity</label>
                    <input type="number" name="stock" class="form-control" required value="<?= e($editProduct['stock'] ?? '50') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Image URL</label>
                <input type="url" name="image_url" class="form-control" required value="<?= e($editProduct['image_url'] ?? 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-control" required placeholder="Detailed product specifications and features..."><?= e($editProduct['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Specs JSON (Optional attributes)</label>
                <textarea name="specs_json" rows="2" class="form-control" placeholder='{"Connectivity": "Bluetooth", "Battery": "40h"}'><?= e($editProduct['specs_json'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="is_active_box" name="is_active" value="1" <?= (!isset($editProduct) || $editProduct['is_active']) ? 'checked' : '' ?>>
                <label for="is_active_box" style="font-size: 13px; cursor: pointer;">Product is active and visible in storefront</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" data-modal-close="product-modal" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
        </form>
    </div>
</div>
