<?php
// app/Controllers/ApiController.php
// Hidden API endpoints (intentionally vulnerable for education)

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\User;

class ApiController
{
    /**
     * INTENTIONAL VULNERABILITY: IDOR (Insecure Direct Object Reference)
     * This endpoint exposes user data without proper authorization checks
     * FLAG: CTF{1d0r_us3r_3num3r4t10n_FTW}
     * 
     * Try: ?route=api/user&id=1
     */
    public function getUserInfo(): void
    {
        $userId = (int)($_GET['id'] ?? 0);
        
        if ($userId <= 0) {
            View::json([
                'success' => false,
                'message' => 'Invalid user ID'
            ], 400);
            return;
        }
        
        // NO AUTHORIZATION CHECK - Intentional vulnerability
        $user = User::findById($userId);
        
        if (!$user) {
            View::json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
            return;
        }
        
        // Expose sensitive information
        View::json([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'created_at' => $user['created_at'],
                // Easter egg flag in response
                'flag' => 'CTF{1d0r_us3r_3num3r4t10n_FTW}'
            ]
        ]);
    }
    
    /**
     * INTENTIONAL VULNERABILITY: Path Traversal / Local File Inclusion
     * This endpoint allows reading arbitrary files from the server
     * FLAG: CTF{p4th_tr4v3rs4l_f1l3_r34d_pwn}
     * 
     * Try: ?route=api/logs&file=../config/config.local.php
     * Or: ?route=api/logs&file=../../../../etc/passwd
     */
    public function viewLogs(): void
    {
        $filename = $_GET['file'] ?? 'app.log';
        
        // NO PATH SANITIZATION - Intentional vulnerability
        $filepath = __DIR__ . '/../../logs/' . $filename;
        
        if (!file_exists($filepath)) {
            View::json([
                'success' => false,
                'message' => 'Log file not found',
                'hint' => 'Try using ../ to navigate directories'
            ], 404);
            return;
        }
        
        $content = file_get_contents($filepath);
        
        View::json([
            'success' => true,
            'file' => $filename,
            'content' => $content,
            'flag' => 'CTF{p4th_tr4v3rs4l_f1l3_r34d_pwn}'
        ]);
    }
}
