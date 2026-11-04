<?php
// app/Controllers/WishlistController.php
// Customer wishlist controller.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Wishlist;

class WishlistController
{
    public function index(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $items = Wishlist::getItems(Auth::id());

        View::render('wishlist/index', [
            'pageTitle' => 'My Wishlist — Amazon Mini',
            'items' => $items,
        ]);
    }

    public function toggle(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $productId = (int)($_POST['product_id'] ?? 0);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($productId <= 0) {
            if ($isAjax) {
                View::json(['success' => false, 'message' => 'Invalid product.'], 400);
            }
            header('Location: ' . url('home'));
            exit;
        }

        $res = Wishlist::toggle(Auth::id(), $productId);

        if ($isAjax) {
            View::json([
                'success' => true,
                'in_wishlist' => $res['in_wishlist'],
                'wishlist_count' => $res['count'],
                'message' => $res['in_wishlist'] ? 'Added to your wishlist.' : 'Removed from your wishlist.',
            ]);
        }

        $_SESSION['flash_success'] = $res['in_wishlist'] ? 'Item added to your wishlist.' : 'Item removed from your wishlist.';
        $redirect = !empty($_POST['redirect']) ? $_POST['redirect'] : url('wishlist');
        header('Location: ' . $redirect);
        exit;
    }
}
