<?php
// app/Models/Wishlist.php
// Customer wishlist items management.

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Wishlist
{
    public static function getItems(int $userId): array
    {
        return Database::fetchAll(
            'SELECT w.id as wishlist_id, w.created_at as added_at, p.*, cat.name as category_name
             FROM wishlist_items w
             JOIN products p ON w.product_id = p.id
             JOIN categories cat ON p.category_id = cat.id
             WHERE w.user_id = ? AND p.is_active = 1
             ORDER BY w.id DESC',
            [$userId]
        );
    }

    public static function isInWishlist(int $userId, int $productId): bool
    {
        $res = Database::fetchOne(
            'SELECT id FROM wishlist_items WHERE user_id = ? AND product_id = ? LIMIT 1',
            [$userId, $productId]
        );
        return $res !== null;
    }

    public static function toggle(int $userId, int $productId): array
    {
        $existing = Database::fetchOne(
            'SELECT id FROM wishlist_items WHERE user_id = ? AND product_id = ? LIMIT 1',
            [$userId, $productId]
        );

        if ($existing) {
            Database::execute('DELETE FROM wishlist_items WHERE id = ?', [$existing['id']]);
            $inWishlist = false;
        } else {
            Database::execute('INSERT INTO wishlist_items (user_id, product_id) VALUES (?, ?)', [$userId, $productId]);
            $inWishlist = true;
        }

        return [
            'in_wishlist' => $inWishlist,
            'count' => self::count($userId),
        ];
    }

    public static function count(int $userId): int
    {
        $res = Database::fetchOne('SELECT COUNT(*) as cnt FROM wishlist_items WHERE user_id = ?', [$userId]);
        return (int)($res['cnt'] ?? 0);
    }
}
