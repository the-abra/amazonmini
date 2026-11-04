<?php
// app/Controllers/ProfileController.php
// Account profile and multi-address management.

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Address;
use App\Models\User;

class ProfileController
{
    public function index(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $user = User::findById(Auth::id());
        $addresses = Address::getByUserId(Auth::id());

        View::render('auth/profile', [
            'pageTitle' => 'My Account & Address Book — Amazon Mini',
            'user' => $user,
            'addresses' => $addresses,
        ]);
    }

    public function addressAdd(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $title = trim($_POST['title'] ?? 'Home');
        $recipient = trim($_POST['recipient_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $line = trim($_POST['address_line'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postal = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? 'United States');
        $isDefault = !empty($_POST['is_default']);

        if (empty($recipient) || empty($phone) || empty($line) || empty($city) || empty($postal)) {
            $_SESSION['flash_error'] = 'Please fill out all required address fields.';
            header('Location: ' . url('profile'));
            exit;
        }

        Address::create(Auth::id(), [
            'title' => $title,
            'recipient_name' => $recipient,
            'phone' => $phone,
            'address_line' => $line,
            'city' => $city,
            'postal_code' => $postal,
            'country' => $country,
            'is_default' => $isDefault,
        ]);

        $_SESSION['flash_success'] = 'New shipping address added to your address book.';
        $redirect = !empty($_POST['redirect']) ? $_POST['redirect'] : url('profile');
        header('Location: ' . $redirect);
        exit;
    }

    public function addressDelete(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $id = (int)($_POST['address_id'] ?? 0);
        if ($id > 0) {
            Address::delete($id, Auth::id());
            $_SESSION['flash_success'] = 'Address removed from your address book.';
        }

        header('Location: ' . url('profile'));
        exit;
    }

    public function addressSetDefault(): void
    {
        Auth::requireRole('customer', 'admin', 'courier');

        $id = (int)($_POST['address_id'] ?? 0);
        if ($id > 0) {
            Address::setDefault($id, Auth::id());
            $_SESSION['flash_success'] = 'Default shipping address updated.';
        }

        header('Location: ' . url('profile'));
        exit;
    }
}
