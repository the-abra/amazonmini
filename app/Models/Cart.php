<?php
// app/Models/Cart.php
// Persistent shopping cart database operations.

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Cart
{
    public static function getItems(int $userId): array
    {
        return Database::fetchAll(
            'SELECT c.id as cart_item_id, c.quantity, p.*, (p.price * c.quantity) as line_total, cat.name as category_name
             FROM cart_items c
             JOIN products p ON c.product_id = p.id
             JOIN categories cat ON p.category_id = cat.id
             WHERE c.user_id = ? AND p.is_active = 1
             ORDER BY c.id DESC',
            [$userId]
        );
    }

    public static function getSummary(int $userId): array
    {
        $items = self::getItems($userId);
        $subtotal = 0.0;
        $totalItems = 0;

        foreach ($items as $item) {
            $subtotal += (float)$item['line_total'];
            $totalItems += (int)$item['quantity'];
        }

        // Free shipping over $50, else $4.99 standard
        $shipping = ($subtotal >= 50.0 || $subtotal == 0) ? 0.0 : 4.99;
        $total = $subtotal + $shipping;

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'shipping' => round($shipping, 2),
            'total' => round($total, 2),
            'count' => $totalItems,
        ];
    }

    public static function add(int $userId, int $productId, int $quantity = 1): bool
    {
        $product = Product::findById($productId);
        if (!$product || $product['stock'] < 1) {
            return false;
        }

        $existing = Database::fetchOne(
            'SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? LIMIT 1',
            [$userId, $productId]
        );

        if ($existing) {
            $newQty = min($product['stock'], (int)$existing['quantity'] + max(1, $quantity));
            return Database::execute(
                'UPDATE cart_items SET quantity = ? WHERE id = ?',
                [$newQty, $existing['id']]
            ) >= 0;
        }

        $qty = min($product['stock'], max(1, $quantity));
        Database::execute(
            'INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)',
            [$userId, $productId, $qty]
        );
        return true;
    }

    public static function update(int $userId, int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return self::remove($userId, $productId);
        }

        $product = Product::findById($productId);
        if (!$product) {
            return false;
        }

        $qty = min($product['stock'], $quantity);
        return Database::execute(
            'UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?',
            [$qty, $userId, $productId]
        ) >= 0;
    }

    public static function remove(int $userId, int $productId): bool
    {
        return Database::execute(
            'DELETE FROM cart_items WHERE user_id = ? AND product_id = ?',
            [$userId, $productId]
        ) > 0;
    }

    public static function clear(int $userId): bool
    {
        return Database::execute('DELETE FROM cart_items WHERE user_id = ?', [$userId]) >= 0;
    }

    public static function count(int $userId): int
    {
        $res = Database::fetchOne('SELECT SUM(quantity) as total_qty FROM cart_items WHERE user_id = ?', [$userId]);
        return (int)($res['total_qty'] ?? 0);
    }
}
