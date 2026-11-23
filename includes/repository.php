<?php
declare(strict_types=1);

/**
 * Menyatukan data CMS (MySQL) dengan fallback config/site.php.
 * Semua query dibungkus try/catch supaya halaman publik tetap hidup
 * saat MySQL belum siap.
 */

function lk_db_ready(): bool
{
    return lk_db() instanceof PDO;
}

function lk_settings_flush(): void
{
    unset($GLOBALS['lk_settings_cache']);
}

function lk_settings_all(): array
{
    if (isset($GLOBALS['lk_settings_cache'])) {
        return $GLOBALS['lk_settings_cache'];
    }
    $all = [];
    $pdo = lk_db();
    if ($pdo) {
        try {
            foreach ($pdo->query('SELECT k, v FROM settings') as $row) {
                $decoded = json_decode((string) $row['v'], true);
                $all[$row['k']] = $decoded === null ? (string) $row['v'] : $decoded;
            }
        } catch (Throwable $error) {
            $GLOBALS['lk_db_error'] = $error->getMessage();
        }
    }
    return $GLOBALS['lk_settings_cache'] = $all;
}

function lk_setting(string $key, $default = null)
{
    $all = lk_settings_all();
    return $all[$key] ?? $default;
}

function lk_setting_save(string $key, $value): bool
{
    $pdo = lk_db();
    if (!$pdo) {
        return false;
    }
    try {
        $statement = $pdo->prepare(
            'INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = NOW()'
        );
        $statement->execute([
            $key,
            json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        lk_settings_flush();
        return true;
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
        return false;
    }
}

function lk_site_config(array $base): array
{
    static $loaded = null;
    if (is_array($loaded)) {
        return $loaded;
    }

    $pdo = lk_db();
    if (!$pdo) {
        return $loaded = $base;
    }

    try {
        $all = lk_settings_all();
        foreach (['business', 'meta', 'auth', 'promo', 'membership', 'legal', 'stats'] as $key) {
            if (isset($all[$key]) && is_array($all[$key])) {
                $base[$key] = array_replace_recursive($base[$key] ?? [], $all[$key]);
            }
        }
        if (isset($all['sequence']) && is_array($all['sequence'])) {
            $base['sequence_folder'] = (string) ($all['sequence']['folder'] ?? $base['sequence_folder']);
            $base['sequence_limit']  = (int) ($all['sequence']['limit'] ?? $base['sequence_limit']);
        }

        $services = $pdo->query(
            'SELECT * FROM services WHERE active = 1 ORDER BY sort ASC, id ASC'
        )->fetchAll();
        if ($services !== []) {
            $mapped = [];
            foreach ($services as $row) {
                $mapped[$row['service_key']] = [
                    'label'          => $row['label'],
                    'unit'           => $row['unit'],
                    'price'          => (int) $row['price'],
                    'min_qty'        => (float) $row['min_qty'],
                    'step'           => (float) $row['step'],
                    'duration_hours' => (int) $row['duration_hours'],
                    'duration_label' => $row['duration_label'],
                    'blurb'          => $row['blurb'],
                    'detail'         => $row['detail'],
                ];
            }
            $base['services'] = $mapped;
        }

        $areas = $pdo->query('SELECT name FROM areas WHERE active = 1 ORDER BY sort ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
        if ($areas !== []) {
            $base['areas'] = array_values($areas);
        }

        $slots = $pdo->query('SELECT label FROM slots WHERE active = 1 ORDER BY sort ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
        if ($slots !== []) {
            $base['pickup_slots'] = array_values($slots);
        }

        $hours = ['outlet' => [], 'courier' => []];
        foreach ($pdo->query('SELECT scope, day, time_label FROM hours ORDER BY scope ASC, sort ASC, id ASC') as $row) {
            $hours[$row['scope']][] = ['day' => $row['day'], 'time' => $row['time_label']];
        }
        foreach (['outlet', 'courier'] as $scope) {
            if ($hours[$scope] !== []) {
                $base['hours'][$scope] = $hours[$scope];
            }
        }

        $testimonials = $pdo->query(
            'SELECT name, service, quote FROM testimonials WHERE active = 1 ORDER BY sort ASC, id ASC'
        )->fetchAll();
        if ($testimonials !== []) {
            $base['testimonials'] = array_map(
                static fn (array $row): array => [
                    'name'    => $row['name'],
                    'service' => $row['service'],
                    'quote'   => $row['quote'],
                ],
                $testimonials
            );
        }

        $guarantees = $pdo->query('SELECT title, body FROM guarantees ORDER BY sort ASC, id ASC')->fetchAll();
        if ($guarantees !== []) {
            $base['guarantees'] = array_map(
                static fn (array $row): array => ['title' => $row['title'], 'text' => $row['body']],
                $guarantees
            );
        }
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }

    return $loaded = $base;
}

/**
 * Status pesanan dipakai di admin dan halaman detail.
 */
function lk_order_statuses(): array
{
    return [
        'baru'        => ['label' => 'Baru', 'tone' => 'info', 'desc' => 'Menunggu konfirmasi admin'],
        'dikonfirmasi'=> ['label' => 'Dikonfirmasi', 'tone' => 'accent', 'desc' => 'Jadwal dan tarif sudah disetujui'],
        'dijemput'    => ['label' => 'Dijemput', 'tone' => 'accent', 'desc' => 'Cucian sudah diambil kurir'],
        'proses'      => ['label' => 'Diproses', 'tone' => 'warn', 'desc' => 'Sedang dicuci dan dikeringkan'],
        'siap'        => ['label' => 'Siap', 'tone' => 'ok', 'desc' => 'Selesai, menunggu diantar'],
        'diantar'     => ['label' => 'Diantar', 'tone' => 'accent', 'desc' => 'Kurir menuju alamat pelanggan'],
        'selesai'     => ['label' => 'Selesai', 'tone' => 'ok', 'desc' => 'Pesanan diterima pelanggan'],
        'batal'       => ['label' => 'Dibatalkan', 'tone' => 'danger', 'desc' => 'Pesanan dibatalkan'],
    ];
}

function lk_order_status_label(string $status): string
{
    return lk_order_statuses()[$status]['label'] ?? ucfirst($status);
}

function lk_generate_order_code(PDO $pdo): string
{
    $prefix = 'LK-' . date('ymd');
    for ($attempt = 0; $attempt < 12; $attempt++) {
        $code = $prefix . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $check = $pdo->prepare('SELECT 1 FROM orders WHERE code = ?');
        $check->execute([$code]);
        if ($check->fetchColumn() === false) {
            return $code;
        }
    }
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Simpan jadwal dari form publik. Mengembalikan baris pesanan atau null.
 */
function lk_create_order(array $values, array $siteConfig, ?string $ip = null): ?array
{
    $pdo = lk_db();
    if (!$pdo) {
        return null;
    }

    try {
        $serviceKey = (string) ($values['layanan'] ?? 'kiloan');
        $service = $siteConfig['services'][$serviceKey] ?? null;
        $weight = $values['berat'] !== '' && is_numeric($values['berat']) ? (float) $values['berat'] : null;
        $estimate = null;
        if ($service && $weight !== null && $weight > 0) {
            $estimate = (int) round((float) $service['price'] * $weight);
        }

        $code = lk_generate_order_code($pdo);
        $statement = $pdo->prepare(
            'INSERT INTO orders
             (code, name, whatsapp, desa, address, pickup_date, slot, service_key, weight, notes, estimate_total, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $code,
            (string) $values['nama'],
            (string) $values['whatsapp'],
            (string) $values['desa'],
            (string) $values['alamat'],
            (string) $values['tanggal'],
            (string) $values['slot'],
            $serviceKey,
            $weight,
            $values['catatan'] !== '' ? (string) $values['catatan'] : null,
            $estimate,
            $ip,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $event = $pdo->prepare('INSERT INTO order_events (order_id, status, note) VALUES (?, ?, ?)');
        $event->execute([$orderId, 'baru', 'Pesanan dibuat dari formulir jadwal jemput.']);

        return ['id' => $orderId, 'code' => $code, 'estimate' => $estimate];
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
        return null;
    }
}
