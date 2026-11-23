<?php
declare(strict_types=1);

function lk_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    session_name('LKADMINSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);
    session_start();
}

function admin_current(): ?array
{
    static $user = null;
    static $resolved = false;
    if ($resolved) {
        return $user;
    }
    $resolved = true;

    $id = (int) ($_SESSION['lk_admin_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    $pdo = lk_db();
    if (!$pdo) {
        return null;
    }
    try {
        $statement = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        if (!$row) {
            unset($_SESSION['lk_admin_id']);
            return null;
        }
        $user = $row;
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
    return $user;
}

function admin_require_login(): array
{
    $user = admin_current();
    if ($user === null) {
        $next = basename((string) ($_SERVER['PHP_SELF'] ?? 'index.php'));
        $query = $_SERVER['QUERY_STRING'] ?? '';
        if ($query !== '') {
            $next .= '?' . $query;
        }
        header('Location: login.php?next=' . urlencode($next));
        exit;
    }
    return $user;
}

function admin_require_owner(): array
{
    $user = admin_require_login();
    if (($user['role'] ?? '') !== 'owner') {
        admin_flash('error', 'Hanya pemilik yang bisa membuka bagian ini.');
        admin_redirect('index.php');
    }
    return $user;
}

function admin_csrf(): string
{
    if (empty($_SESSION['lk_admin_csrf']) || empty($_SESSION['lk_admin_csrf_time'])
        || (time() - (int) $_SESSION['lk_admin_csrf_time']) > 7200) {
        $_SESSION['lk_admin_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['lk_admin_csrf_time'] = time();
    }
    return $_SESSION['lk_admin_csrf'];
}

function admin_csrf_valid(?string $token): bool
{
    if (!is_string($token) || $token === '' || empty($_SESSION['lk_admin_csrf'])) {
        return false;
    }
    if (empty($_SESSION['lk_admin_csrf_time']) || (time() - (int) $_SESSION['lk_admin_csrf_time']) > 7200) {
        return false;
    }
    return hash_equals($_SESSION['lk_admin_csrf'], $token);
}

function admin_check_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    if (admin_csrf_valid($_POST['_token'] ?? null)) {
        return;
    }
    admin_flash('error', 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu simpan lagi.');
    $back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    $path = parse_url($back, PHP_URL_PATH) ?: 'index.php';
    $file = basename($path);
    $query = parse_url($back, PHP_URL_QUERY);
    header('Location: ' . $file . ($query ? '?' . $query : ''));
    exit;
}

function admin_login_attempt(string $email, string $password): bool
{
    $now = time();
    $attempts = array_values(array_filter(
        is_array($_SESSION['lk_admin_attempts'] ?? null) ? $_SESSION['lk_admin_attempts'] : [],
        static fn ($stamp) => is_int($stamp) && ($now - $stamp) < 600
    ));

    if (count($attempts) >= 6) {
        admin_flash('error', 'Terlalu banyak percobaan. Tunggu 10 menit lalu coba lagi.');
        return false;
    }

    $pdo = lk_db();
    if (!$pdo) {
        admin_flash('error', 'Database belum tersambung. Periksa MySQL lalu muat ulang.');
        return false;
    }

    $statement = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? LIMIT 1');
    $statement->execute([strtolower(trim($email))]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        $attempts[] = $now;
        $_SESSION['lk_admin_attempts'] = array_slice($attempts, -12);
        usleep(250000);
        admin_flash('error', 'Email atau kata sandi tidak cocok.');
        return false;
    }

    unset($_SESSION['lk_admin_attempts']);
    session_regenerate_id(true);
    $_SESSION['lk_admin_id'] = (int) $user['id'];
    unset($_SESSION['lk_admin_csrf']);

    try {
        $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([(int) $user['id']]);
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
    admin_audit('login', 'Masuk ke dashboard');
    return true;
}

function admin_logout(): void
{
    unset($_SESSION['lk_admin_id'], $_SESSION['lk_admin_csrf'], $_SESSION['lk_admin_csrf_time']);
    session_regenerate_id(true);
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['lk_admin_flashes'][] = ['type' => $type, 'message' => $message];
}

function admin_flashes(): array
{
    $items = is_array($_SESSION['lk_admin_flashes'] ?? null) ? $_SESSION['lk_admin_flashes'] : [];
    unset($_SESSION['lk_admin_flashes']);
    return $items;
}

function admin_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function admin_audit(string $action, string $detail = ''): void
{
    $pdo = lk_db();
    if (!$pdo) {
        return;
    }
    try {
        $statement = $pdo->prepare('INSERT INTO admin_activity (admin_id, action, detail) VALUES (?, ?, ?)');
        $statement->execute([
            (int) ($_SESSION['lk_admin_id'] ?? 0) ?: null,
            $action,
            mb_substr($detail, 0, 190, 'UTF-8'),
        ]);
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}

function admin_post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? clean_input($_POST[$key]) : $default;
}

function admin_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}
