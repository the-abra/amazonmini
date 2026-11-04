<?php
// app/Views/product/view.php
use App\Core\Auth;
?>
<div style="margin-bottom: 40px;">
    <!-- Breadcrumb -->
    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
        <a href="<?= url('home') ?>">Store</a> &rsaquo; 
        <a href="<?= url('home', ['category' => $product['category_id']]) ?>"><?= e($product['category_name']) ?></a> &rsaquo;
        <span style="color: var(--text-primary);"><?= e($product['name']) ?></span>
    </div>

    <!-- Product Showcase -->
    <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 36px; margin-bottom: 40px;">
        <!-- Left: Image -->
        <div class="card" style="padding: 10px; display: flex; align-items: center; justify-content: center; background: #ffffff;">
            <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" style="max-height: 440px; object-fit: contain; width: 100%; border-radius: var(--radius-sm);">
        </div>

        <!-- Right: Purchasing details & info -->
        <div>
            <div style="font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px;">
                SKU: <span class="notranslate" translate="no"><?= e($product['sku']) ?></span>
            </div>
            <h1 style="font-size: 28px; font-weight: 700; line-height: 1.3; margin-bottom: 12px;"><?= e($product['name']) ?></h1>

            <div class="product-rating" style="font-size: 15px; margin-bottom: 16px;">
                <span>★</span>
                <span class="rating-num"><?= number_format((float)$product['rating_avg'], 1) ?></span>
                <span class="rating-count">&bull; <?= (int)$product['review_count'] ?> customer ratings</span>
            </div>

            <div style="font-size: 32px; font-weight: 800; color: var(--text-primary); margin-bottom: 16px;">
                <?= money($product['price']) ?>
            </div>

            <div style="font-size: 14px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 24px;">
                <?= nl2br(e($product['description'])) ?>
            </div>

            <!-- Stock status -->
            <div style="margin-bottom: 20px;">
                <?php if ($product['stock'] > 10): ?>
                    <span style="color: var(--accent); font-weight: 600; font-size: 14px;">In Stock (<?= (int)$product['stock'] ?> units ready to ship)</span>
                <?php elseif ($product['stock'] > 0): ?>
                    <span style="color: var(--warning); font-weight: 600; font-size: 14px;">Only <?= (int)$product['stock'] ?> left in stock — order soon</span>
                <?php else: ?>
                    <span style="color: var(--danger); font-weight: 600; font-size: 14px;">Currently Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <?php if ($product['stock'] > 0): ?>
                <form id="product-purchase-form" action="<?= url('cart/add') ?>" method="POST" style="margin-bottom: 18px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
                        <label style="font-size: 13px; font-weight: 700; color: var(--text-secondary);">Quantity:</label>
                        <div class="qty-spinner">
                            <button type="button" class="qty-spinner-btn" data-dir="down" aria-label="Decrease quantity">&minus;</button>
                            <input type="number" name="quantity" class="qty-spinner-input" value="1" min="1" max="<?= min(10, (int)$product['stock']) ?>" readonly>
                            <button type="button" class="qty-spinner-btn" data-dir="up" aria-label="Increase quantity">&plus;</button>
                        </div>
                        <span style="font-size: 12px; color: var(--text-muted);">(Max 10 per order)</span>
                    </div>

                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary btn-lg" style="flex: 1; min-width: 180px;" data-action="add-to-cart-form">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            Add to Cart
                        </button>

                        <button type="button" class="btn btn-accent btn-lg" style="flex: 1; min-width: 180px;" data-action="buy-now" data-product-id="<?= $product['id'] ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            Buy Now
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div style="margin-bottom: 24px;">
                <button type="button" class="btn btn-secondary btn-block <?= $inWishlist ? 'active' : '' ?>" 
                        data-action="toggle-wishlist" data-product-id="<?= $product['id'] ?>" style="padding: 11px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $inWishlist ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span><?= $inWishlist ? 'In Your Wishlist (Click to remove)' : 'Add to Wishlist' ?></span>
                </button>
            </div>

            <!-- Value Props & Trust Badges Grid -->
            <div class="trust-badges-grid">
                <div class="trust-badge-item">
                    <div class="trust-badge-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    </div>
                    <div>
                        <strong style="display: block; color: var(--text-primary); font-size: 13px;">Fast Delivery</strong>
                        <span style="font-size: 12px; color: var(--text-muted);">Express dispatch within 24h</span>
                    </div>
                </div>

                <div class="trust-badge-item">
                    <div class="trust-badge-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <div>
                        <strong style="display: block; color: var(--text-primary); font-size: 13px;">Secure Sandbox</strong>
                        <span style="font-size: 12px; color: var(--text-muted);">256-bit Stripe test gateway</span>
                    </div>
                </div>

                <div class="trust-badge-item">
                    <div class="trust-badge-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                    <div>
                        <strong style="display: block; color: var(--text-primary); font-size: 13px;">Free Returns</strong>
                        <span style="font-size: 12px; color: var(--text-muted);">30-day hassle-free demo returns</span>
                    </div>
                </div>

                <div class="trust-badge-item">
                    <div class="trust-badge-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    </div>
                    <div>
                        <strong style="display: block; color: var(--text-primary); font-size: 13px;">Cargo Tracking</strong>
                        <span style="font-size: 12px; color: var(--text-muted);">Real-time facility checkpoint logs</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Specifications Table (if available) -->
    <?php if (!empty($specs)): ?>
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">Technical Specifications</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                <?php foreach ($specs as $key => $val): ?>
                    <div style="background: var(--bg-subtle); padding: 12px; border-radius: var(--radius-sm); font-size: 13px;">
                        <span style="color: var(--text-muted); display: block; font-weight: 500;"><?= e($key) ?></span>
                        <strong style="color: var(--text-primary);"><?= e($val) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Customer Reviews Section -->
    <div class="card">
        <div class="card-header">
            <h2 style="font-size: 18px; font-weight: 700;">Customer Reviews (<?= count($reviews) ?>)</h2>
            <?php if (Auth::check() && !$hasReviewed): ?>
                <button type="button" class="btn btn-sm btn-secondary" data-modal-open="write-review-modal">
                    Write a Review
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($reviews)): ?>
            <p style="color: var(--text-muted); font-size: 14px; padding: 16px 0;">No reviews written yet. Be the first to share your thoughts!</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($reviews as $rev): ?>
                    <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span style="color: #eab308; font-size: 14px;"><?= str_repeat('★', (int)$rev['rating']) ?></span>
                            <strong style="font-size: 14px; color: var(--text-primary);"><?= e($rev['title']) ?></strong>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">
                            By <?= e($rev['user_name']) ?> &bull; <?= date('M d, Y', strtotime($rev['created_at'])) ?>
                        </div>
                        <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">
                            <?php 
                            // INTENTIONAL VULNERABILITY: XSS for educational purposes
                            // This demonstrates the importance of output escaping
                            // FLAG: CTF{xss_r3v13w_pwn3d_2024}
                            echo nl2br($rev['comment']); 
                            ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Write Review Modal -->
<?php if (Auth::check()): ?>
<div id="write-review-modal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 18px; font-weight: 700;">Review <?= e($product['name']) ?></h3>
            <button type="button" data-modal-close="write-review-modal" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>

        <form action="<?= url('product/review') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

            <div class="form-group">
                <label class="form-label">Overall Rating</label>
                <select name="rating" class="form-control" required>
                    <option value="5">★★★★★ (5 Stars - Excellent)</option>
                    <option value="4">★★★★☆ (4 Stars - Good)</option>
                    <option value="3">★★★☆☆ (3 Stars - Average)</option>
                    <option value="2">★★☆☆☆ (2 Stars - Fair)</option>
                    <option value="1">★☆☆☆☆ (1 Star - Poor)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Review Headline / Title</label>
                <input type="text" name="title" class="form-control" required placeholder="What's most important to know?">
            </div>

            <div class="form-group">
                <label class="form-label">Written Feedback</label>
                <textarea name="comment" rows="4" class="form-control" required placeholder="Describe product quality, performance, and experience..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" data-modal-close="write-review-modal" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
