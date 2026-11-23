<?php
declare(strict_types=1);

/* ----------------------------------------------------------------------------
   2. Helpers: escaping, placeholders, currency, CSRF, request handling.
   ------------------------------------------------------------------------- */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_placeholder(?string $value): bool
{
    if ($value === null) {
        return true;
    }
    $trimmed = trim($value);
    return $trimmed === '' || preg_match('/^\[.+\]$/u', $trimmed) === 1;
}

function rupiah(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function text_len(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function csrf_token(): string
{
    if (empty($_SESSION['lkj_csrf']) || empty($_SESSION['lkj_csrf_time'])
        || (time() - (int) $_SESSION['lkj_csrf_time']) > 7200) {
        $_SESSION['lkj_csrf']      = bin2hex(random_bytes(32));
        $_SESSION['lkj_csrf_time'] = time();
    }
    return $_SESSION['lkj_csrf'];
}

function csrf_valid(?string $token): bool
{
    if (!is_string($token) || $token === '' || empty($_SESSION['lkj_csrf'])) {
        return false;
    }
    if (empty($_SESSION['lkj_csrf_time']) || (time() - (int) $_SESSION['lkj_csrf_time']) > 7200) {
        return false;
    }
    return hash_equals($_SESSION['lkj_csrf'], $token);
}

function csrf_rotate(): string
{
    unset($_SESSION['lkj_csrf'], $_SESSION['lkj_csrf_time']);
    return csrf_token();
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function json_out(array $payload): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function lk_session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);
    session_start();
}

function clean_input(?string $value): string
{
    $value = (string) $value;
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    return trim($value);
}
