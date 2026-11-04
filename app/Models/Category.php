<?php
// app/Models/Category.php
// Category catalog model.

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Category
{
    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM categories ORDER BY name ASC');
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM categories WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne('SELECT * FROM categories WHERE slug = ?', [$slug]);
    }

    public static function create(array $data): int
    {
        $slug = self::generateSlug($data['name']);
        Database::execute(
            'INSERT INTO categories (name, slug, description, icon_name) VALUES (?, ?, ?, ?)',
            [trim($data['name']), $slug, trim($data['description'] ?? ''), trim($data['icon_name'] ?? 'folder')]
        );
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = ['name = ?', 'description = ?', 'icon_name = ?'];
        $params = [trim($data['name']), trim($data['description'] ?? ''), trim($data['icon_name'] ?? 'folder')];
        if (!empty($data['slug'])) {
            $fields[] = 'slug = ?';
            $params[] = self::generateSlug($data['slug']);
        }
        $params[] = $id;

        return Database::execute('UPDATE categories SET ' . implode(', ', $fields) . ' WHERE id = ?', $params) > 0;
    }

    public static function generateSlug(string $text): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
        return $slug ?: 'cat-' . substr(md5(uniqid()), 0, 6);
    }
}
