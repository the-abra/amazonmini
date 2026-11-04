<?php
// app/Views/home/index.php
use App\Core\Auth;
$user = Auth::user();
$isFirstVisit = !Auth::check() && !isset($_COOKIE['welcome_seen']);
?>

<?php if ($isFirstVisit && empty($_GET['q']) && empty($_GET['category'])): ?>
<!-- Welcome Hero Banner -->
<div class="card" style="background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; padding: 40px; margin-bottom: 30px; border: none; box-shadow: var(--shadow-lg);">
    <div style="max-width: 700px; margin: 0 auto; text-align: center;">
        <h1 style="font-size: 36px; font-weight: 800; margin-bottom: 12px; color: white;">
            Welcome to Amazon Mini! 🛍️
        </h1>
        <p style="font-size: 16px; margin-bottom: 24px; opacity: 0.95; line-height: 1.6;">
            Your one-stop shop for amazing products with <strong>free shipping on orders $50+</strong>.<br>
            Browse our catalog, track your orders in real-time, and enjoy seamless checkout with Stripe.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="<?= url('auth/register') ?>" class="btn btn-lg" style="background: white; color: var(--primary); font-weight: 700; border: none;">
                Get Started
            </a>
            <a href="<?= url('auth/login') ?>" class="btn btn-lg btn-secondary" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid white;">
                Sign In
            </a>
        </div>
    </div>
</div>
<script>
    // Mark welcome as seen
    document.cookie = 'welcome_seen=1; path=/; max-age=2592000'; // 30 days
</script>
<?php endif; ?>

<div style="margin-bottom: 30px;">
    <!-- Active Filter & Sorting Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 22px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px;">
                <?= !empty($filters['query']) ? 'Results for "' . e($filters['query']) . '"' : 'Featured Products' ?>
            </h1>
            <div style="color: var(--text-muted); font-size: 13px; margin-top: 3px;">
                <?= $paginator->total ?> items available with fast express courier dispatch &bull; page <?= $paginator->page ?> of <?= $paginator->totalPages ?>
                <?php if (!empty($filters['query']) || !empty($filters['category_id'])): ?>
                    &bull; <a href="<?= url('home') ?>" style="font-weight: 600; text-decoration: underline;">Clear all filters</a>
                <?php endif; ?>
            </div>
        </div>

        <form action="index.php" method="GET" style="display: flex; align-items: center; gap: 10px;">
            <input type="hidden" name="route" value="home">
            <?php if (!empty($filters['category_id'])): ?>
                <input type="hidden" name="category" value="<?= e($filters['category_id']) ?>">
            <?php endif; ?>
            <?php if (!empty($filters['query'])): ?>
                <input type="hidden" name="q" value="<?= e($filters['query']) ?>">
            <?php endif; ?>

            <label for="sort-select" style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">Sort By:</label>
            <select id="sort-select" name="sort" class="form-control" style="width: auto; padding: 7px 14px; font-size: 13px; font-weight: 600;" onchange="this.form.submit()">
                <option value="featured" <?= ($filters['sort'] === 'featured') ? 'selected' : '' ?>>Featured & Top Rated</option>
                <option value="price_asc" <?= ($filters['sort'] === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= ($filters['sort'] === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="newest" <?= ($filters['sort'] === 'newest') ? 'selected' : '' ?>>Newest Arrivals</option>
            </select>
        </form>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <svg style="margin: 0 auto 16px auto; color: var(--text-muted);" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 8px;">No products found</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 22px;">Try adjusting your search terms or department filters.</p>
            <a href="<?= url('home') ?>" class="btn btn-primary">Reset Filters</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $prod): ?>
                <?php 
                    $inWish = in_array((int)$prod['id'], $wishlistIds, true); 
                    $priceVal = (float)$prod['price'];
                    $hasFreeShipping = ($priceVal >= 50.00);
                ?>
                <div class="product-card">
                    <div class="product-image-wrap">
                        <a href="<?= url('product/view', ['id' => $prod['id']]) ?>">
                            <img src="<?= e($prod['image_url']) ?>" alt="<?= e($prod['name']) ?>" class="product-image" loading="lazy">
                        </a>

                        <?php if ($hasFreeShipping): ?>
                            <span class="badge-free-shipping">FREE Shipping</span>
                        <?php endif; ?>

                        <?php if ($prod['stock'] > 0 && $prod['stock'] <= 5): ?>
                            <span style="position: absolute; bottom: 10px; left: 10px; background: rgba(217, 119, 6, 0.95); color: #ffffff; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-sm); z-index: 4; box-shadow: var(--shadow-xs);">
                                Only <?= (int)$prod['stock'] ?> Left
                            </span>
                        <?php endif; ?>

                        <button type="button" class="wishlist-btn-overlay <?= $inWish ? 'active' : '' ?>" 
                                data-action="toggle-wishlist" 
                                data-product-id="<?= $prod['id'] ?>" 
                                title="<?= $inWish ? 'Remove from wishlist' : 'Save to wishlist' ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $inWish ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        </button>
                    </div>

                    <div class="product-card-body">
                        <div class="product-category-tag">
                            <a href="<?= url('home', ['category' => $prod['category_id']]) ?>" style="color: inherit;"><?= e($prod['category_name']) ?></a>
                        </div>
                        <h2 class="product-title">
                            <a href="<?= url('product/view', ['id' => $prod['id']]) ?>">
                                <?= e($prod['name']) ?>
                            </a>
                        </h2>

                        <div class="product-rating">
                            <span>★</span>
                            <span class="rating-num"><?= number_format((float)$prod['rating_avg'], 1) ?></span>
                            <span class="rating-count">(<?= (int)$prod['review_count'] ?> reviews)</span>
                        </div>

                        <div class="product-price-row">
                            <div class="product-price"><?= money($prod['price']) ?></div>
                            <?php if ($prod['stock'] > 0): ?>
                                <button type="button" class="btn-quick-add" data-action="add-to-cart" data-product-id="<?= $prod['id'] ?>">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                                    Add
                                </button>
                            <?php else: ?>
                                <span style="font-size: 12px; color: var(--danger); font-weight: 700; background: var(--danger-light); padding: 4px 8px; border-radius: var(--radius-sm);">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php require __DIR__ . '/../partials/pagination.php'; ?>
    <?php endif; ?>
</div>
