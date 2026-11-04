<?php
// app/Views/wishlist/index.php
?>
<div style="max-width: 900px; margin: 20px auto;">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">My Wishlist</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Items saved for future reference and quick ordering.</p>
        </div>
        <a href="<?= url('home') ?>" class="btn btn-secondary">Explore More Products</a>
    </div>

    <?php if (empty($items)): ?>
        <div class="card" style="text-align: center; padding: 50px 20px;">
            <svg style="margin: 0 auto 16px auto; color: var(--text-muted);" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <h3 style="font-size: 18px; margin-bottom: 8px;">Your wishlist is currently empty.</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Click the heart icon on any product card to save it here.</p>
            <a href="<?= url('home') ?>" class="btn btn-primary">Browse Catalog</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($items as $item): ?>
                <div class="card" style="display: flex; gap: 20px; align-items: center; margin-bottom: 0;">
                    <a href="<?= url('product/view', ['id' => $item['id']]) ?>" style="width: 100px; height: 100px; flex-shrink: 0; background: var(--bg-subtle); border-radius: var(--radius-sm); overflow: hidden;">
                        <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </a>

                    <div style="flex: 1;">
                        <div style="font-size: 12px; color: var(--text-muted);"><?= e($item['category_name']) ?></div>
                        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 6px;">
                            <a href="<?= url('product/view', ['id' => $item['id']]) ?>" style="color: inherit;"><?= e($item['name']) ?></a>
                        </h3>
                        <div style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">
                            <?= money($item['price']) ?>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            Added on <?= date('M d, Y', strtotime($item['added_at'])) ?>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; min-width: 130px;">
                        <button type="button" class="btn btn-primary btn-sm" data-action="add-to-cart" data-product-id="<?= $item['id'] ?>">
                            Add to Cart
                        </button>
                        <form action="<?= url('wishlist/toggle') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm btn-block" style="color: var(--danger);">
                                Remove
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
