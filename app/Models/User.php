<?php
// app/Models/User.php
// User model managing customer, admin, and courier accounts.

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public static function findById(int $id): ?array
    {
        return Database::fetchOne('SELECT id, name, email, role, phone, created_at FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne('SELECT * FROM users WHERE email = ?', [trim($email)]);
    }

    public static function create(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        Database::execute(
            'INSERT INTO users (name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)',
            [
                trim($data['name']),
                trim($data['email']),
                $hash,
                $data['role'] ?? 'customer',
                $data['phone'] ?? null
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = trim($data['name']);
        }
        if (isset($data['phone'])) {
            $fields[] = 'phone = ?';
            $params[] = trim($data['phone']);
        }
        if (!empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        return Database::execute($sql, $params) > 0;
    }

    public static function getCouriers(): array
    {
        return Database::fetchAll("SELECT id, name, email, phone FROM users WHERE role = 'courier' ORDER BY name ASC");
    }

    public static function countByRole(string $role): int
    {
        $res = Database::fetchOne('SELECT COUNT(*) as cnt FROM users WHERE role = ?', [$role]);
        return (int)($res['cnt'] ?? 0);
    }
}
