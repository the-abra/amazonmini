<?php
// app/Controllers/CourierController.php
// Dedicated courier logistics portal for dispatch, hub scans, and delivery confirmation.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Shipment;
use App\Services\OrderService;

class CourierController
{
    public function __construct()
    {
        Auth::requireRole('courier', 'admin');
    }

    public function dashboard(): void
    {
        $statusFilter = $_GET['status'] ?? null;
        $courierId = Auth::role() === 'courier' ? Auth::id() : null;

        $shipments = Shipment::all([
            'courier_id' => $courierId,
            'status' => $statusFilter,
        ]);

        $selectedTracking = $_GET['track'] ?? null;
        $selectedShipment = null;
        if ($selectedTracking) {
            $selectedShipment = Shipment::findByTrackingNumber($selectedTracking);
        }

        View::render('courier/dashboard', [
            'pageTitle' => 'Logistics Officer Portal — Amazon Mini Logistics',
            'shipments' => $shipments,
            'selectedShipment' => $selectedShipment,
            'statusFilter' => $statusFilter,
            'statuses' => Shipment::allStatuses(),
            'nextStatuses' => $selectedShipment ? Shipment::allowedNext($selectedShipment['current_status']) : [],
            'failureReasons' => Shipment::FAILURE_REASONS,
            'maxAttempts' => Shipment::MAX_DELIVERY_ATTEMPTS,
        ]);
    }

    public function updateStatus(): void
    {
        $shipmentId = (int)($_POST['shipment_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($shipmentId <= 0 || empty($newStatus) || empty($location)) {
            $_SESSION['flash_error'] = 'Please specify shipment, checkpoint status, and physical facility location.';
            header('Location: ' . url('courier/dashboard'));
            exit;
        }

        $reason = trim($_POST['reason_code'] ?? '') ?: null;
        $shipment = Database::fetchOne('SELECT tracking_number, order_id FROM shipments WHERE id = ?', [$shipmentId]);
        $redirect = url('courier/dashboard', ['track' => $shipment['tracking_number'] ?? '']);

        try {
            $success = Shipment::updateStatus($shipmentId, $newStatus, $location, $notes ?: 'Status updated by courier.', Auth::id(), $reason);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: ' . $redirect);
            exit;
        }

        if ($success) {
            $updated = Shipment::findByOrderId((int)$shipment['order_id']);
            if (($updated['current_status'] ?? '') === 'returned_to_sender') {
                $config = require dirname(__DIR__, 2) . '/config/env.php';
                (new OrderService($config))->processReturn((int)$shipment['order_id']);
                $_SESSION['flash_error'] = 'Third failed attempt: package marked Returned to Sender, stock restored and customer refunded.';
            } else {
                $_SESSION['flash_success'] = 'Shipment checkpoint updated to: ' . (Shipment::allStatuses()[$newStatus]['label'] ?? $newStatus);
            }
        } else {
            $_SESSION['flash_error'] = 'Failed to update shipment status.';
        }

        header('Location: ' . url('courier/dashboard'));
        exit;
    }
}
