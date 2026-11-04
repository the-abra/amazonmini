<?php
// app/Controllers/HomeController.php
// Storefront landing and catalog controller.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Paginator;
use App\Core\View;
use App\Models\Category;
use App\Models\Product;
use App\Models\Wishlist;

class HomeController
{
    public function index(): void
    {
        $categories = Category::all();

        $filters = [
            'query' => $_GET['q'] ?? '',
            'category_id' => !empty($_GET['category']) ? (int)$_GET['category'] : null,
            'sort' => $_GET['sort'] ?? 'featured',
            'min_price' => $_GET['min_price'] ?? null,
            'max_price' => $_GET['max_price'] ?? null,
        ];

        $config = require dirname(__DIR__, 2) . '/config/env.php';
        $perPage = max(1, (int)($config['items_per_page'] ?? 12));
        $paginator = new Paginator(Product::count($filters), $perPage, (int)($_GET['page'] ?? 1));
        $filters['limit'] = $perPage;
        $filters['offset'] = $paginator->offset();

        $products = Product::search($filters);

        $wishlistIds = [];
        if (Auth::check()) {
            $wishlistItems = Wishlist::getItems(Auth::id());
            $wishlistIds = array_column($wishlistItems, 'id');
        }

        View::render('home/index', [
            'pageTitle' => 'Amazon Mini Clone — Clean Modular E-Commerce',
            'categories' => $categories,
            'products' => $products,
            'filters' => $filters,
            'paginator' => $paginator,
            'wishlistIds' => $wishlistIds,
        ]);
    }
}
