<?php
use App\Core\Auth;
use App\Core\Cart;
use App\Core\Telemetry;
use App\Models\Cart as CartModel;
use App\Models\Category as CategoryModel;
use App\Models\Wishlist as WishlistModel;

$cartCount = Auth::check() ? CartModel::count(Auth::id()) : 0;
$wishlistCount = Auth::check() ? WishlistModel::count(Auth::id()) : 0;
$user = Auth::user();
$currentRoute = $_GET['route'] ?? 'home';
$allCategories = CategoryModel::all();
$activeCategoryId = (int)($_GET['category'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle ?? 'Amazon Mini Clone — Modular E-Commerce') ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body id="app-root">

    <!-- Main Navigation Header -->
    <header class="site-header">
        <div class="container header-main">
            <a href="<?= url('home') ?>" class="logo" data-action="nav-home">
                <span class="logo-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </span>
                <span>Amazon<span style="color: var(--primary);">Mini</span></span>
            </a>

            <!-- Search Form -->
            <form action="index.php" method="GET" class="search-form">
                <input type="hidden" name="route" value="home">
                <input type="text" name="q" class="search-input" placeholder="Search products, brands, SKUs..." value="<?= e($_GET['q'] ?? '') ?>">
                <button type="submit" class="search-button">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Search
                </button>
            </form>

            <!-- Navigation Links -->
            <nav class="nav-actions">
                <a href="<?= url('tracking/track') ?>" class="nav-action-link" data-action="nav-track" title="Track Cargo & Packages">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    <div>
                        <span style="display:block; font-size:11px; color:var(--text-muted); line-height:1;">Logistics</span>
                        <span class="nav-action-bold">Track Cargo</span>
                    </div>
                </a>

                <?php if ($user): ?>
                    <a href="<?= url('wishlist') ?>" class="nav-action-link" data-action="nav-wishlist" title="View Wishlist">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        <div>
                            <span style="display:block; font-size:11px; color:var(--text-muted); line-height:1;">Favorites</span>
                            <span class="nav-action-bold">Wishlist <span id="wishlist-badge" class="badge-count" style="<?= $wishlistCount > 0 ? '' : 'display:none;' ?>"><?= $wishlistCount ?></span></span>
                        </div>
                    </a>

                    <a href="<?= url('cart') ?>" class="nav-action-link" data-action="nav-cart" title="View Shopping Cart">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <div>
                            <span style="display:block; font-size:11px; color:var(--text-muted); line-height:1;">Checkout</span>
                            <span class="nav-action-bold">Cart <span id="cart-badge" class="badge-count" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span></span>
                        </div>
                    </a>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <a href="<?= url('profile') ?>" class="nav-action-link" data-action="nav-profile" title="Account Settings">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <div>
                                <span style="display:block; font-size:11px; color:var(--text-muted); line-height:1;">Hello, <?= e($user['name']) ?></span>
                                <span class="nav-action-bold"><?= ucfirst($user['role']) ?> Profile</span>
                            </div>
                        </a>

                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="<?= url('admin/dashboard') ?>" class="btn btn-sm btn-secondary" style="border-color: var(--primary); color: var(--primary);">Admin Portal</a>
                        <?php elseif ($user['role'] === 'courier'): ?>
                            <a href="<?= url('courier/dashboard') ?>" class="btn btn-sm btn-secondary" style="border-color: var(--accent); color: var(--accent);">Courier Portal</a>
                        <?php endif; ?>

                        <a href="<?= url('auth/logout') ?>" class="btn btn-sm btn-secondary" title="Sign Out">Sign Out</a>
                    </div>
                <?php else: ?>
                    <a href="<?= url('auth/login') ?>" class="btn btn-sm btn-secondary">Sign In</a>
                    <a href="<?= url('auth/register') ?>" class="btn btn-sm btn-primary">Register</a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Category Quick Navigation Sub-Bar -->
        <nav class="category-nav-bar">
            <div class="container category-nav-inner">
                <a href="<?= url('home') ?>" class="cat-pill <?= ($activeCategoryId === 0 && empty($_GET['q'])) ? 'active' : '' ?>">All Departments</a>
                <?php foreach ($allCategories as $cat): ?>
                    <a href="<?= url('home', ['category' => $cat['id']]) ?>" class="cat-pill <?= ($activeCategoryId === (int)$cat['id']) ? 'active' : '' ?>">
                        <?= e($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="app-main">
        <div class="container">
            <!-- Flash Message Alerts -->
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="flash-alert flash-success">
                    <span><?= e($_SESSION['flash_success']) ?></span>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="flash-alert flash-error">
                    <span><?= e($_SESSION['flash_error']) ?></span>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container footer-inner">
            <div>
                &copy; <?= date('Y') ?> Amazon Mini Clone. Open Source MIT Educational Project.
            </div>
            <div style="display: flex; gap: 16px; align-items: center;">
                <a href="<?= url('tracking/track') ?>">Cargo Tracker</a>
                <a href="<?= url('health') ?>">System Status</a>
                <a href="<?= url('home') ?>">Store Catalog</a>
                <a href="https://github.com" target="_blank" rel="noopener">GitHub Repository</a>
            </div>
        </div>
    </footer>

    <!-- Toast Notification Container -->
    <div id="toast-container"></div>

    <!-- Backend PHP Telemetry Bridge (Structured JSON for Browser DevTools Console) -->
    <script id="php-telemetry-data" type="application/json">
        <?= Telemetry::renderJson() ?>
    </script>

    <!-- Telemetry & Application Scripts -->
    <script src="assets/js/telemetry.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
