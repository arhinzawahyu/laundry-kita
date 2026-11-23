<?php
declare(strict_types=1);

/* ============================================================================
   Akun pelanggan: sesi, daftar, masuk, keluar, dan tautan ke pesanan.
   Sesi memakai sesi publik yang sudah berjalan (lihat index.php / halaman akun).
   ========================================================================= */

function customer_wa_normalize(?string $value): string
{
    $digits = preg_replace('/[^0-9+]/', '', (string) $value) ?? '';
    if (str_starts_with($digits, '+62')) {
        $digits = '62' . substr($digits, 3);
    } elseif (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }
    return $digits;
}

function customer_wa_display(string $value): string
{
    if (str_starts_with($value, '62') && strlen($value) > 4) {
        return '0' . substr($value, 2);
    }
    return $value;
}

function customer_current(): ?array
{
    static $customer = null;
    static $resolved = false;
    if ($resolved) {
        return $customer;
    }
    $resolved = true;

    $id = (int) ($_SESSION['lk_customer_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    $pdo = lk_db();
    if (!$pdo) {
        return null;
    }
    try {
        $statement = $pdo->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        if (!$row) {
            unset($_SESSION['lk_customer_id']);
            return null;
        }
        $customer = $row;
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
    return $customer;
}

function customer_flash(string $type, string $message): void
{
    $_SESSION['lk_customer_flash'][] = ['type' => $type, 'message' => $message];
}

function customer_flashes(): array
{
    $flashes = $_SESSION['lk_customer_flash'] ?? [];
    unset($_SESSION['lk_customer_flash']);
    return is_array($flashes) ? $flashes : [];
}

function customer_attempt_allowed(): bool
{
    $now = time();
    $log = array_values(array_filter($_SESSION['lk_customer_attempts'] ?? [], static fn ($t) => is_int($t) && ($now - $t) < 600));
    $_SESSION['lk_customer_attempts'] = $log;
    return count($log) < 8;
}

function customer_attempt_log(): void
{
    $_SESSION['lk_customer_attempts'][] = time();
}

function customer_start_session(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['lk_customer_id'] = $id;
    unset($_SESSION['lk_customer_attempts']);
}

function customer_login(string $email, string $password): bool
{
    $pdo = lk_db();
    if (!$pdo) {
        customer_flash('error', 'Database belum tersambung. Coba lagi sebentar lagi.');
        return false;
    }
    if (!customer_attempt_allowed()) {
        customer_flash('error', 'Terlalu banyak percobaan masuk. Tunggu 10 menit lalu coba lagi.');
        return false;
    }
    $statement = $pdo->prepare('SELECT * FROM customers WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $row = $statement->fetch();
    customer_attempt_log();

    if (!$row || !password_verify($password, (string) $row['password_hash'])) {
        customer_flash('error', 'Email atau kata sandi belum cocok.');
        return false;
    }
    if (password_needs_rehash((string) $row['password_hash'], PASSWORD_DEFAULT)) {
        $pdo->prepare('UPDATE customers SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    $pdo->prepare('UPDATE customers SET last_login_at = NOW() WHERE id = ?')->execute([$row['id']]);
    customer_start_session((int) $row['id']);
    customer_flash('success', 'Selamat datang kembali, ' . $row['name'] . '.');
    return true;
}

/**
 * Daftar akun pelanggan. Mengembalikan id pelanggan atau null; pesan galat
 * dikumpulkan pada $errors dengan kunci nama kolom.
 */
function customer_register(array $input, array &$errors): ?int
{
    $pdo = lk_db();
    if (!$pdo) {
        $errors['form'] = 'Database belum tersambung. Coba lagi sebentar lagi.';
        return null;
    }

    $name = clean_input($input['name'] ?? '');
    $email = strtolower(clean_input($input['email'] ?? ''));
    $wa = customer_wa_normalize($input['whatsapp'] ?? '');
    $password = (string) ($input['password'] ?? '');
    $confirm = (string) ($input['password_confirm'] ?? '');

    if (text_len($name) < 2 || text_len($name) > 60) {
        $errors['name'] = 'Tulis nama Anda, 2 sampai 60 karakter.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Masukkan email yang valid.';
    }
    if (preg_match('/^628[1-9][0-9]{6,11}$/', $wa) !== 1) {
        $errors['whatsapp'] = 'Nomor WhatsApp belum valid. Contoh: 0812 3456 7890.';
    }
    if (strlen($password) < 10) {
        $errors['password'] = 'Kata sandi minimal 10 karakter.';
    } elseif (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Campur huruf dan angka agar lebih aman.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Konfirmasi kata sandi belum sama.';
    }

    if ($errors === []) {
        $check = $pdo->prepare('SELECT 1 FROM customers WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetchColumn() !== false) {
            $errors['email'] = 'Email ini sudah dipakai. Masuk saja lewat halaman masuk.';
        }
    }

    if ($errors !== []) {
        return null;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO customers (name, email, whatsapp, password_hash) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$name, $email, $wa, password_hash($password, PASSWORD_DEFAULT)]);
        $id = (int) $pdo->lastInsertId();
        customer_start_session($id);
        customer_flash('success', 'Akun dibuat. Selamat datang, ' . $name . '.');
        return $id;
    } catch (Throwable $error) {
        $errors['form'] = 'Akun gagal dibuat. Coba lagi sebentar lagi.';
        $GLOBALS['lk_db_error'] = $error->getMessage();
        return null;
    }
}

function customer_logout(): void
{
    unset($_SESSION['lk_customer_id']);
    session_regenerate_id(true);
}

/* ------------------------------------------------------------------ pesanan */

function customer_attach_order(int $orderId, int $customerId): void
{
    $pdo = lk_db();
    if (!$pdo) {
        return;
    }
    try {
        $pdo->prepare('UPDATE orders SET customer_id = ? WHERE id = ? AND customer_id IS NULL')
            ->execute([$customerId, $orderId]);
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}

/**
 * Hubungkan pesanan lama ke akun. Aman karena butuh kode pesanan + nomor
 * WhatsApp yang sama dengan data pesanan.
 */
function customer_claim_order(string $code, string $whatsapp, array $customer): ?string
{
    $pdo = lk_db();
    if (!$pdo) {
        return 'Database belum tersambung.';
    }
    $code = strtoupper(trim($code));
    if ($code === '' || strlen($code) > 24) {
        return 'Masukkan kode pesanan, contoh LK-260110-A1B2.';
    }
    $statement = $pdo->prepare('SELECT * FROM orders WHERE code = ? LIMIT 1');
    $statement->execute([$code]);
    $order = $statement->fetch();
    if (!$order) {
        return 'Kode pesanan tidak ditemukan. Periksa kembali pesan konfirmasi dari kami.';
    }
    if ($order['customer_id'] !== null && (int) $order['customer_id'] !== (int) $customer['id']) {
        return 'Pesanan ini sudah terhubung ke akun lain.';
    }
    if (customer_wa_normalize($order['whatsapp']) !== customer_wa_normalize($customer['whatsapp'])) {
        return 'Nomor WhatsApp akun ini tidak sama dengan nomor pada pesanan.';
    }
    $pdo->prepare('UPDATE orders SET customer_id = ? WHERE id = ?')
        ->execute([$customer['id'], $order['id']]);
    return null;
}

function customer_orders(int $customerId, int $limit = 40): array
{
    $pdo = lk_db();
    if (!$pdo) {
        return [];
    }
    try {
        $statement = $pdo->prepare(
            'SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit
        );
        $statement->execute([$customerId]);
        return $statement->fetchAll();
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
        return [];
    }
}

function customer_order_events(array $orderIds): array
{
    $pdo = lk_db();
    if (!$pdo || $orderIds === []) {
        return [];
    }
    $ids = array_map('intval', $orderIds);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
        $statement = $pdo->prepare(
            'SELECT * FROM order_events WHERE order_id IN (' . $placeholders . ') ORDER BY created_at ASC, id ASC'
        );
        $statement->execute($ids);
        $grouped = [];
        foreach ($statement->fetchAll() as $event) {
            $grouped[(int) $event['order_id']][] = $event;
        }
        return $grouped;
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
        return [];
    }
}

function customer_order_stats(array $orders): array
{
    $stats = ['total' => count($orders), 'aktif' => 0, 'selesai' => 0];
    foreach ($orders as $order) {
        $status = (string) $order['status'];
        if ($status === 'selesai') {
            $stats['selesai']++;
        } elseif ($status !== 'batal') {
            $stats['aktif']++;
        }
    }
    return $stats;
}

/** Urutan tahap yang ditampilkan di halaman akun (tanpa status batal). */
function customer_status_track(): array
{
    return ['baru', 'dikonfirmasi', 'dijemput', 'proses', 'siap', 'diantar', 'selesai'];
}

function customer_status_index(string $status): int
{
    $index = array_search($status, customer_status_track(), true);
    return $index === false ? -1 : (int) $index;
}

function customer_service_label(array $siteConfig, string $key): string
{
    return $siteConfig['services'][$key]['label'] ?? ucfirst($key);
}
