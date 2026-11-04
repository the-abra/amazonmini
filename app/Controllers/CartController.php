<?php
// app/Controllers/CartController.php
// Shopping cart actions with dual support for AJAX JSON and Form POST.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Cart;

class CartController
{
    public function index(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $summary = Cart::getSummary(Auth::id());

        View::render('cart/index', [
            'pageTitle' => 'Shopping Cart — Amazon Mini',
            'summary' => $summary,
        ]);
    }

    public function add(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($productId <= 0) {
            if ($isAjax) {
                View::json(['success' => false, 'message' => 'Invalid product identifier.'], 400);
            }
            $_SESSION['flash_error'] = 'Invalid product.';
            header('Location: ' . url('home'));
            exit;
        }

        $added = Cart::add(Auth::id(), $productId, $quantity);

        if ($isAjax) {
            $totalCount = Cart::count(Auth::id());
            View::json([
                'success' => $added,
                'cart_count' => $totalCount,
                'message' => $added ? 'Item added to your cart.' : 'Unable to add item (out of stock).',
            ]);
        }

        if ($added) {
            $_SESSION['flash_success'] = 'Item added to your cart.';
        } else {
            $_SESSION['flash_error'] = 'Unable to add item to cart (check stock availability).';
        }

        $redirect = !empty($_POST['redirect']) ? $_POST['redirect'] : url('cart');
        header('Location: ' . $redirect);
        exit;
    }

    public function update(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        Cart::update(Auth::id(), $productId, $quantity);

        if ($isAjax) {
            $summary = Cart::getSummary(Auth::id());
            View::json([
                'success' => true,
                'cart_count' => $summary['count'],
                'subtotal' => $summary['subtotal'],
                'shipping' => $summary['shipping'],
                'total' => $summary['total'],
            ]);
        }

        $_SESSION['flash_success'] = 'Cart updated.';
        header('Location: ' . url('cart'));
        exit;
    }

    public function remove(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $productId = (int)($_POST['product_id'] ?? 0);
        Cart::remove(Auth::id(), $productId);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isAjax) {
            $summary = Cart::getSummary(Auth::id());
            View::json([
                'success' => true,
                'cart_count' => $summary['count'],
                'total' => $summary['total'],
            ]);
        }

        $_SESSION['flash_success'] = 'Item removed from your cart.';
        header('Location: ' . url('cart'));
        exit;
    }
}
