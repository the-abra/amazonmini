<?php
// app/Models/Review.php
// Product reviews and rating aggregation.

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Review
{
    public static function getByProductId(int $productId): array
    {
        return Database::fetchAll(
            'SELECT r.*, u.name as user_name 
             FROM reviews r 
             JOIN users u ON r.user_id = u.id 
             WHERE r.product_id = ? 
             ORDER BY r.id DESC',
            [$productId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO reviews (product_id, user_id, rating, title, comment) VALUES (?, ?, ?, ?, ?)',
            [
                (int)$data['product_id'],
                (int)$data['user_id'],
                max(1, min(5, (int)$data['rating'])),
                trim($data['title']),
                trim($data['comment'])
            ]
        );

        $id = (int)Database::lastInsertId();
        Product::recalculateRating((int)$data['product_id']);
        return $id;
    }

    public static function hasUserReviewed(int $userId, int $productId): bool
    {
        $res = Database::fetchOne(
            'SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1',
            [$userId, $productId]
        );
        return $res !== null;
    }
}
