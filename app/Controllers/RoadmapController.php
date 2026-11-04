<?php
// app/Controllers/RoadmapController.php
// CTF Challenge roadmap and hints

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\CTFParticipant;

class RoadmapController
{
    public function index(): void
    {
        $token = $_COOKIE['ctf_token'] ?? null;
        $currentParticipant = null;
        $progress = null;
        
        if ($token) {
            $currentParticipant = CTFParticipant::findByToken($token);
            if ($currentParticipant) {
                $progress = CTFParticipant::getProgress((int)$currentParticipant['id']);
            }
        }
        
        $allFlags = CTFParticipant::getAllFlags();
        
        View::render('ctf/roadmap', [
            'pageTitle' => 'CTF Challenge Roadmap — Amazon Mini',
            'currentParticipant' => $currentParticipant,
            'progress' => $progress,
            'allFlags' => $allFlags,
        ]);
    }
}
