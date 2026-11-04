<?php
// app/Models/Address.php
// Multi-address book management per customer.

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Address
{
    public static function getByUserId(int $userId): array
    {
        return Database::fetchAll(
            'SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC',
            [$userId]
        );
    }

    public static function findByIdAndUser(int $id, int $userId): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM addresses WHERE id = ? AND user_id = ? LIMIT 1',
            [$id, $userId]
        );
    }

    public static function getDefault(int $userId): ?array
    {
        $addr = Database::fetchOne(
            'SELECT * FROM addresses WHERE user_id = ? AND is_default = 1 LIMIT 1',
            [$userId]
        );
        if (!$addr) {
            $addr = Database::fetchOne(
                'SELECT * FROM addresses WHERE user_id = ? ORDER BY id ASC LIMIT 1',
                [$userId]
            );
        }
        return $addr;
    }

    public static function create(int $userId, array $data): int
    {
        $existing = self::getByUserId($userId);
        $isDefault = (empty($existing) || !empty($data['is_default'])) ? 1 : 0;

        if ($isDefault === 1) {
            Database::execute('UPDATE addresses SET is_default = 0 WHERE user_id = ?', [$userId]);
        }

        Database::execute(
            'INSERT INTO addresses (user_id, title, recipient_name, phone, address_line, city, postal_code, country, is_default)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                trim($data['title'] ?? 'Home'),
                trim($data['recipient_name']),
                trim($data['phone']),
                trim($data['address_line']),
                trim($data['city']),
                trim($data['postal_code']),
                trim($data['country'] ?? 'United States'),
                $isDefault
            ]
        );

        return (int)Database::lastInsertId();
    }

    public static function delete(int $id, int $userId): bool
    {
        $addr = self::findByIdAndUser($id, $userId);
        if (!$addr) {
            return false;
        }

        Database::execute('DELETE FROM addresses WHERE id = ? AND user_id = ?', [$id, $userId]);

        // If the deleted address was default, make another one default
        if ($addr['is_default']) {
            $next = Database::fetchOne('SELECT id FROM addresses WHERE user_id = ? ORDER BY id ASC LIMIT 1', [$userId]);
            if ($next) {
                Database::execute('UPDATE addresses SET is_default = 1 WHERE id = ?', [$next['id']]);
            }
        }

        return true;
    }

    public static function setDefault(int $id, int $userId): bool
    {
        Database::beginTransaction();
        try {
            Database::execute('UPDATE addresses SET is_default = 0 WHERE user_id = ?', [$userId]);
            Database::execute('UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
            Database::commit();
            return true;
        } catch (\Throwable $e) {
            Database::rollBack();
            return false;
        }
    }
}
