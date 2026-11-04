<?php
// app/Core/Auth.php
// Session-based authentication and role guard management.

declare(strict_types=1);

namespace App\Core;

class Auth
{
    private const IDLE_TIMEOUT = 7200;
    private const MAX_FAILED_LOGINS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function start(): void
    {
        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();

            // Expire idle sessions server-side, not only via the cookie.
            if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > self::IDLE_TIMEOUT) {
                $_SESSION = [];
                session_regenerate_id(true);
            }
            $_SESSION['last_activity'] = time();
        }
    }

    public static function check(): bool
    {
        self::start();
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $currentRole = self::role();
        if (!$currentRole) {
            return false;
        }
        return in_array($currentRole, $roles, true);
    }

    public static function requireRole(string ...$roles): void
    {
        if (!self::check()) {
            if (is_ajax()) {
                View::json([
                    'success' => false,
                    'require_login' => true,
                    'redirect' => url('auth/login'),
                    'message' => 'Please sign in to continue.',
                ], 401);
            }
            $_SESSION['flash_error'] = 'Please log in to continue.';
            header('Location: ?route=auth/login');
            exit;
        }

        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            Telemetry::logTerminalEndpoint('AUTH', 'forbidden', 403, 'Role unauthorized');
            if (is_ajax()) {
                View::json([
                    'success' => false,
                    'message' => 'You do not have permission to access this area.',
                ], 403);
            }
            View::render('home/error', [
                'statusCode' => 403,
                'title' => 'Access Denied',
                'message' => 'You do not have permission to access this area.',
            ]);
            exit;
        }
    }

    public static function login(array $user): void
    {
        self::start();
        // Prevent session fixation: issue a fresh session id (and CSRF token) on every privilege change.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        unset($_SESSION['csrf_token']);

        // Do not store password hash in session
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        Telemetry::recordEvent('user_logged_in', ['id' => $user['id'], 'role' => $user['role']]);
    }

    public static function logout(): void
    {
        self::start();
        $userId = self::id();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        Telemetry::recordEvent('user_logged_out', ['id' => $userId]);
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = trim($email);
        $user = Database::fetchOne('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        if ($user && password_verify($password, $user['password_hash'])) {
            Database::execute('DELETE FROM login_attempts WHERE email = ? AND ip_address = ?', [$email, self::clientIp()]);
            self::login($user);
            return true;
        }
        Database::execute('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)', [$email, self::clientIp()]);
        return false;
    }

    public static function clientIp(): string
    {
        return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }

    /** Brute-force protection: too many recent failures for this e-mail + IP pair. */
    public static function isLockedOut(string $email): bool
    {
        $row = Database::fetchOne(
            'SELECT COUNT(*) AS cnt FROM login_attempts WHERE email = ? AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)',
            [trim($email), self::clientIp(), self::LOCKOUT_MINUTES]
        );
        return (int)($row['cnt'] ?? 0) >= self::MAX_FAILED_LOGINS;
    }

    public static function lockoutMinutes(): int
    {
        return self::LOCKOUT_MINUTES;
    }

    public static function loginAsDemo(string $role): bool
    {
        $validRoles = ['customer', 'admin', 'courier'];
        if (!in_array($role, $validRoles, true)) {
            return false;
        }

        $user = Database::fetchOne('SELECT * FROM users WHERE role = ? ORDER BY id ASC LIMIT 1', [$role]);
        if ($user) {
            self::login($user);
            return true;
        }
        return false;
    }
}
