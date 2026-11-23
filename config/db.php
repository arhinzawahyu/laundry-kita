<?php
declare(strict_types=1);

/**
 * Koneksi database untuk situs dan admin.
 * Kredensial ada di config/database.php, bisa ditimpa lewat env LK_DB_*.
 */
function lk_db_config(): array
{
    $config = require __DIR__ . '/database.php';
    foreach (['host', 'port', 'name', 'user', 'pass'] as $key) {
        $env = getenv('LK_DB_' . strtoupper($key));
        if ($env !== false && $env !== '') {
            $config[$key] = $key === 'port' ? (int) $env : $env;
        }
    }
    return $config;
}

function lk_db(): ?PDO
{
    static $pdo = null;
    static $tried = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if ($tried) {
        return null;
    }
    $tried = true;

    $config = lk_db_config();
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $server = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=%s', $config['host'], (int) $config['port'], $config['charset']),
            (string) $config['user'],
            (string) $config['pass'],
            $options
        );
        $name = preg_replace('/[^A-Za-z0-9_]/', '', (string) $config['name']) ?: 'laundry_kita';
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $server = null;

        $pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                (int) $config['port'],
                $name,
                $config['charset']
            ),
            (string) $config['user'],
            (string) $config['pass'],
            $options
        );

        $pdo->exec("SET time_zone = '+07:00'");
        lk_db_bootstrap($pdo);
        lk_db_migrate($pdo);
        return $pdo;
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
        $pdo = null;
        return null;
    }
}

function lk_db_error(): string
{
    return (string) ($GLOBALS['lk_db_error'] ?? '');
}

function lk_db_bootstrap(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $hasSettings = $pdo->query("SHOW TABLES LIKE 'settings'")->fetchColumn();
    if ($hasSettings === false) {
        $schema = dirname(__DIR__) . '/database/schema.sql';
        $sql = is_file($schema) ? (string) file_get_contents($schema) : '';
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $pdo->exec($statement);
        }
        lk_db_seed($pdo);
        return;
    }

    $empty = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn() === 0;
    $noServices = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 0;
    if ($empty && $noServices) {
        lk_db_seed($pdo);
    }
}

/**
 * Migrasi ringan yang aman dijalankan berulang: menambah tabel/kolom baru
 * tanpa menghapus data yang sudah ada.
 */
function lk_db_migrate(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS customers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(80) NOT NULL,
                email VARCHAR(190) NOT NULL,
                whatsapp VARCHAR(24) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                last_login_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_customer_email (email),
                KEY idx_customer_whatsapp (whatsapp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $column = $pdo->query("SHOW COLUMNS FROM orders LIKE 'customer_id'");
        if ($column !== false && $column->fetch() === false) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN customer_id INT UNSIGNED NULL AFTER source, ADD KEY idx_order_customer (customer_id)');
        }
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}

function lk_db_seed(PDO $pdo): void
{
    $defaults = require __DIR__ . '/site.php';

    $settings = [];
    foreach (['business', 'meta', 'auth', 'promo', 'membership', 'legal', 'stats'] as $key) {
        if (isset($defaults[$key])) {
            $settings[$key] = $defaults[$key];
        }
    }
    $settings['sequence'] = [
        'folder' => $defaults['sequence_folder'] ?? 'sequence',
        'limit'  => $defaults['sequence_limit'] ?? 240,
    ];

    $insertSetting = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
    foreach ($settings as $key => $value) {
        $insertSetting->execute([
            $key,
            json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    $insertService = $pdo->prepare(
        'INSERT IGNORE INTO services
         (service_key, label, unit, price, min_qty, step, duration_hours, duration_label, blurb, detail, sort)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $sort = 0;
    foreach ($defaults['services'] as $key => $service) {
        $insertService->execute([
            $key,
            $service['label'],
            $service['unit'],
            (int) $service['price'],
            (float) $service['min_qty'],
            (float) $service['step'],
            (int) $service['duration_hours'],
            $service['duration_label'],
            $service['blurb'],
            $service['detail'],
            $sort++,
        ]);
    }

    $insertArea = $pdo->prepare('INSERT IGNORE INTO areas (name, sort) VALUES (?, ?)');
    $sort = 0;
    foreach ($defaults['areas'] as $area) {
        $insertArea->execute([$area, $sort++]);
    }

    $insertSlot = $pdo->prepare('INSERT IGNORE INTO slots (label, sort) VALUES (?, ?)');
    $sort = 0;
    foreach ($defaults['pickup_slots'] as $slot) {
        $insertSlot->execute([$slot, $sort++]);
    }

    $insertHour = $pdo->prepare('INSERT INTO hours (scope, day, time_label, sort) VALUES (?, ?, ?, ?)');
    $sort = 0;
    foreach ($defaults['hours']['outlet'] as $row) {
        $insertHour->execute(['outlet', $row['day'], $row['time'], $sort++]);
    }
    $sort = 0;
    foreach ($defaults['hours']['courier'] as $row) {
        $insertHour->execute(['courier', $row['day'], $row['time'], $sort++]);
    }

    $insertTestimonial = $pdo->prepare(
        'INSERT INTO testimonials (name, service, quote, sort) VALUES (?, ?, ?, ?)'
    );
    $sort = 0;
    foreach ($defaults['testimonials'] as $row) {
        $insertTestimonial->execute([$row['name'], $row['service'], $row['quote'], $sort++]);
    }

    $insertGuarantee = $pdo->prepare('INSERT INTO guarantees (title, body, sort) VALUES (?, ?, ?)');
    $sort = 0;
    foreach ($defaults['guarantees'] as $row) {
        $insertGuarantee->execute([$row['title'], $row['text'], $sort++]);
    }

    // Demo owner account so the panel is usable immediately. Replace the email
    // and password from Admin > Akun after the first login.
    if ((int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO admin_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
            ->execute([
                'Admin Demo',
                'admin@golaundry.id',
                password_hash('GoLaundry2026!', PASSWORD_DEFAULT),
                'owner',
            ]);
    }
}
