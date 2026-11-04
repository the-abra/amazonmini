<?php
// app/Controllers/AuthController.php
// User authentication and 1-click educational demo login.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

class AuthController
{
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirectByRole(Auth::role());
            return;
        }

        View::render('auth/login', [
            'pageTitle' => 'Sign In — Amazon Mini Clone',
        ]);
    }

    public function loginPost(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Please enter both your email address and password.';
            header('Location: ' . url('auth/login'));
            exit;
        }

        if (Auth::isLockedOut($email)) {
            $_SESSION['flash_error'] = 'Too many failed sign-in attempts. Please try again in ' . Auth::lockoutMinutes() . ' minutes.';
            header('Location: ' . url('auth/login'));
            exit;
        }

        if (Auth::attempt($email, $password)) {
            $_SESSION['flash_success'] = 'Welcome back, ' . htmlspecialchars(Auth::user()['name']) . '!';
            $this->redirectByRole(Auth::role());
            return;
        }

        $_SESSION['flash_error'] = 'Invalid email address or password.';
        header('Location: ' . url('auth/login'));
        exit;
    }

    public function register(): void
    {
        if (Auth::check()) {
            header('Location: ' . url('home'));
            exit;
        }

        View::render('auth/register', [
            'pageTitle' => 'Create Account — Amazon Mini Clone',
        ]);
    }

    public function registerPost(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['password_confirm'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Please fill out all required fields.';
            header('Location: ' . url('auth/register'));
            exit;
        }

        if ($password !== $confirmPassword) {
            $_SESSION['flash_error'] = 'Passwords do not match.';
            header('Location: ' . url('auth/register'));
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Password must be at least 6 characters.';
            header('Location: ' . url('auth/register'));
            exit;
        }

        $existing = User::findByEmail($email);
        if ($existing) {
            $_SESSION['flash_error'] = 'An account with this email address already exists.';
            header('Location: ' . url('auth/register'));
            exit;
        }

        $userId = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => 'customer',
        ]);

        $user = User::findById($userId);
        Auth::login($user);

        $_SESSION['flash_success'] = 'Your account has been created successfully! Welcome to Amazon Mini.';
        header('Location: ' . url('home'));
        exit;
    }



    public function demoLogin(): void
    {
        $role = $_GET['role'] ?? 'customer';
        
        // Map roles to demo accounts
        $demoAccounts = [
            'customer' => 'customer@example.com',
            'admin' => 'admin@example.com',
            'courier' => 'courier@example.com',
        ];
        
        // Validate role
        if (!isset($demoAccounts[$role])) {
            $_SESSION['flash_error'] = 'Invalid demo role.';
            header('Location: ' . url('auth/login'));
            exit;
        }
        
        // Find user by email
        $user = User::findByEmail($demoAccounts[$role]);
        
        if (!$user) {
            $_SESSION['flash_error'] = 'Demo account not found. Please run database seed.';
            header('Location: ' . url('auth/login'));
            exit;
        }
        
        // Login user
        Auth::login($user);
        
        $roleNames = [
            'customer' => 'Customer',
            'admin' => 'Admin',
            'courier' => 'Courier',
        ];
        
        $_SESSION['flash_success'] = '✅ Welcome! You\'re now logged in as ' . $roleNames[$role] . '.';
        $this->redirectByRole($user['role']);
    }

    public function logout(): void
    {
        Auth::logout();
        $_SESSION['flash_success'] = 'You have been successfully signed out.';
        header('Location: ' . url('home'));
        exit;
    }

    private function redirectByRole(?string $role): void
    {
        switch ($role) {
            case 'admin':
                header('Location: ' . url('admin/dashboard'));
                break;
            case 'courier':
                header('Location: ' . url('courier/dashboard'));
                break;
            default:
                header('Location: ' . url('home'));
                break;
        }
        exit;
    }
}
