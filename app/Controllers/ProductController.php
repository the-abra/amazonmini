<?php
// app/Controllers/ProductController.php
// Product details and customer review actions.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Product;
use App\Models\Review;
use App\Models\Wishlist;

class ProductController
{
    public function view(): void
    {
        $id = !empty($_GET['id']) ? (int)$_GET['id'] : null;
        $slug = $_GET['slug'] ?? null;

        $product = null;
        if ($id) {
            $product = Product::findById($id);
        } elseif ($slug) {
            $product = Product::findBySlug($slug);
        }

        if (!$product) {
            http_response_code(404);
            View::render('home/error', [
                'statusCode' => 404,
                'title' => 'Product Not Found',
                'message' => 'The requested product could not be located in our catalog.',
            ]);
            return;
        }

        $reviews = Review::getByProductId((int)$product['id']);
        $inWishlist = Auth::check() ? Wishlist::isInWishlist(Auth::id(), (int)$product['id']) : false;
        $hasReviewed = Auth::check() ? Review::hasUserReviewed(Auth::id(), (int)$product['id']) : false;

        $specs = [];
        if (!empty($product['specs_json'])) {
            $decoded = json_decode($product['specs_json'], true);
            if (is_array($decoded)) {
                $specs = $decoded;
            }
        }

        View::render('product/view', [
            'pageTitle' => $product['name'] . ' — Product Details',
            'product' => $product,
            'reviews' => $reviews,
            'specs' => $specs,
            'inWishlist' => $inWishlist,
            'hasReviewed' => $hasReviewed,
        ]);
    }

    public function submitReview(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $productId = (int)($_POST['product_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 5);
        $title = trim($_POST['title'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        if ($productId <= 0 || empty($title) || empty($comment)) {
            $_SESSION['flash_error'] = 'Please fill out all review fields.';
            header('Location: ' . url('product/view', ['id' => $productId]));
            exit;
        }

        Review::create([
            'product_id' => $productId,
            'user_id' => Auth::id(),
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
        ]);

        $_SESSION['flash_success'] = 'Thank you! Your product review has been submitted.';

        header('Location: ' . url('product/view', ['id' => $productId]));
        exit;
    }
}
