<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

admin_logout();
admin_flash('success', 'Anda sudah keluar dari dashboard.');
admin_redirect('login.php');
