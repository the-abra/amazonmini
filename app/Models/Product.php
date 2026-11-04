<?php
// app/Models/Product.php
// Product catalog and inventory management model.

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Product
{
    private static function buildWhere(array $filters): array
    {
        $sql = ' WHERE 1=1';
        $params = [];

        if (!isset($filters['include_inactive']) || !$filters['include_inactive']) {
            $sql .= ' AND p.is_active = 1';
        }

        if (!empty($filters['category_id'])) {
            $sql .= ' AND p.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['query'])) {
            $sql .= ' AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)';
            $like = '%' . trim($filters['query']) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['min_price'])) {
            $sql .= ' AND p.price >= ?';
            $params[] = (float)$filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= ' AND p.price <= ?';
            $params[] = (float)$filters['max_price'];
        }

        return [$sql, $params];
    }

    public static function count(array $filters = []): int
    {
        [$where, $params] = self::buildWhere($filters);
        $row = Database::fetchOne('SELECT COUNT(*) AS cnt FROM products p JOIN categories c ON p.category_id = c.id' . $where, $params);
        return (int)($row['cnt'] ?? 0);
    }

    public static function search(array $filters = []): array
    {
        [$where, $params] = self::buildWhere($filters);
        $sql = 'SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM products p
                JOIN categories c ON p.category_id = c.id' . $where;

        $sort = $filters['sort'] ?? 'featured';
        switch ($sort) {
            case 'price_asc':
                $sql .= ' ORDER BY p.price ASC, p.id DESC';
                break;
            case 'price_desc':
                $sql .= ' ORDER BY p.price DESC, p.id DESC';
                break;
            case 'rating':
                $sql .= ' ORDER BY p.rating_avg DESC, p.review_count DESC, p.id DESC';
                break;
            case 'newest':
                $sql .= ' ORDER BY p.created_at DESC, p.id DESC';
                break;
            default:
                $sql .= ' ORDER BY p.rating_avg DESC, p.id DESC';
                break;
        }

        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT p.*, c.name as category_name, c.slug as category_slug 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE p.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne(
            'SELECT p.*, c.name as category_name, c.slug as category_slug 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE p.slug = ? LIMIT 1',
            [$slug]
        );
    }

    public static function featured(int $limit = 8): array
    {
        return self::search(['limit' => $limit, 'sort' => 'featured']);
    }

    public static function create(array $data): int
    {
        $slug = self::generateSlug($data['name']);
        Database::execute(
            'INSERT INTO products (category_id, sku, name, slug, description, price, stock, image_url, specs_json, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int)$data['category_id'],
                trim($data['sku']),
                trim($data['name']),
                $slug,
                trim($data['description']),
                (float)$data['price'],
                (int)($data['stock'] ?? 0),
                trim($data['image_url'] ?? ''),
                !empty($data['specs_json']) ? $data['specs_json'] : null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = [
            'category_id = ?',
            'sku = ?',
            'name = ?',
            'description = ?',
            'price = ?',
            'stock = ?',
            'image_url = ?',
            'specs_json = ?',
            'is_active = ?'
        ];
        $params = [
            (int)$data['category_id'],
            trim($data['sku']),
            trim($data['name']),
            trim($data['description']),
            (float)$data['price'],
            (int)($data['stock'] ?? 0),
            trim($data['image_url'] ?? ''),
            !empty($data['specs_json']) ? $data['specs_json'] : null,
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ];

        if (!empty($data['slug'])) {
            $fields[] = 'slug = ?';
            $params[] = self::generateSlug($data['slug']);
        }

        $params[] = $id;
        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?';
        return Database::execute($sql, $params) > 0;
    }

    public static function delete(int $id): bool
    {
        return Database::execute('DELETE FROM products WHERE id = ?', [$id]) > 0;
    }

    public static function decrementStock(int $id, int $quantity, ?int $orderId = null): bool
    {
        $res = Database::execute('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$quantity, $id, $quantity]);
        if ($res > 0) {
            self::logMovement($id, -$quantity, 'order_paid', $orderId);
        }
        return $res > 0;
    }

    public static function restock(int $id, int $quantity, string $reason, ?int $orderId = null): void
    {
        Database::execute('UPDATE products SET stock = stock + ? WHERE id = ?', [$quantity, $id]);
        self::logMovement($id, $quantity, $reason, $orderId);
    }

    public static function logMovement(int $productId, int $delta, string $reason, ?int $orderId = null): void
    {
        Database::execute(
            'INSERT INTO stock_movements (product_id, order_id, delta, reason) VALUES (?, ?, ?, ?)',
            [$productId, $orderId, $delta, $reason]
        );
    }

    public static function lowStock(int $threshold = 5, int $limit = 6): array
    {
        return Database::fetchAll(
            'SELECT id, name, sku, stock FROM products WHERE is_active = 1 AND stock <= ? ORDER BY stock ASC, id ASC LIMIT ' . (int)$limit,
            [$threshold]
        );
    }

    public static function recalculateRating(int $productId): void
    {
        $stats = Database::fetchOne(
            'SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM reviews WHERE product_id = ?',
            [$productId]
        );
        $avg = round((float)($stats['avg_rating'] ?? 0.0), 2);
        $count = (int)($stats['cnt'] ?? 0);

        Database::execute(
            'UPDATE products SET rating_avg = ?, review_count = ? WHERE id = ?',
            [$avg, $count, $productId]
        );
    }

    public static function generateSlug(string $text): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
        return $slug ?: 'prod-' . substr(md5(uniqid()), 0, 6);
    }
}
