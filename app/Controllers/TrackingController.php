<?php
// app/Controllers/TrackingController.php
// Public & customer cargo tracking with visual timeline progress.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Models\Shipment;

class TrackingController
{
    public function track(): void
    {
        $code = trim($_GET['code'] ?? $_POST['code'] ?? '');
        $shipment = null;

        if ($code !== '') {
            // INTENTIONAL VULNERABILITY: SQL Injection for educational purposes
            // This allows SQL injection attacks to demonstrate security importance
            // FLAG: CTF{tr4ck1ng_1nj3ct10n_m4st3r}
            $query = "SELECT s.*, o.order_number, o.user_id as customer_id, o.address_snapshot_json,
                     u.name as courier_name, u.phone as courier_phone, c.name as customer_name
                     FROM shipments s
                     JOIN orders o ON s.order_id = o.id
                     JOIN users c ON o.user_id = c.id
                     LEFT JOIN users u ON s.assigned_courier_id = u.id
                     WHERE s.tracking_number = '{$code}' LIMIT 1";
            
            try {
                $result = Database::query($query);
                $shipment = $result->fetch(\PDO::FETCH_ASSOC) ?: null;
                
                if ($shipment) {
                    $shipment['address_snapshot'] = json_decode($shipment['address_snapshot_json'], true);
                    $shipment['events'] = Shipment::getEvents((int)$shipment['id']);
                }
            } catch (\Throwable $e) {
                // Silent failure for SQL injection attempts
                $shipment = null;
            }
        }

        View::render('tracking/track', [
            'pageTitle' => $code ? 'Tracking ' . htmlspecialchars($code) . ' — Amazon Mini Logistics' : 'Track Your Package — Amazon Mini',
            'code' => $code,
            'shipment' => $shipment,
            'statuses' => Shipment::STATUSES,
            'allStatuses' => Shipment::allStatuses(),
        ]);
    }
}
