<?php
declare(strict_types=1);

/* Keluar dari akun pelanggan: hanya lewat POST dengan token CSRF. */

date_default_timezone_set('Asia/Jakarta');
$siteConfig = require __DIR__ . '/config/site.php';

require_once __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/customer.php';

lk_session_boot();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: akun.php');
    exit;
}

if (csrf_valid($_POST['csrf_token'] ?? null)) {
    customer_logout();
    customer_flash('success', 'Anda sudah keluar dari akun.');
    header('Location: login.php');
    exit;
}

customer_flash('error', 'Permintaan keluar tidak dikenali. Coba lagi.');
header('Location: akun.php');
exit;
