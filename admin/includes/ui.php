<?php
declare(strict_types=1);

function admin_icon(string $name, int $size = 20): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>',
        'orders'    => '<path d="M6 8V6a6 6 0 1 1 12 0v2"/><rect x="3" y="8" width="18" height="13" rx="3"/><path d="M8 12h8"/>',
        'services'  => '<path d="M12 3 3 8l9 5 9-5-9-5Z"/><path d="m3 13 9 5 9-5"/><path d="m3 17.5 9 5 9-5"/>',
        'promo'     => '<path d="M20 12V5a2 2 0 0 0-2-2h-7L3.5 10.5a2.5 2.5 0 0 0 0 3.5l6.5 6.5a2.5 2.5 0 0 0 3.5 0L20 12Z"/><circle cx="15.5" cy="8.5" r="1.5"/>',
        'areas'     => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>',
        'hours'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'star'      => '<path d="m12 3 2.7 5.7 6.3.9-4.6 4.4 1.1 6.2L12 17.3 6.5 20.2l1.1-6.2L3 9.6l6.3-.9L12 3Z"/>',
        'shield'    => '<path d="M12 3 5 6v6c0 4.2 2.9 7.6 7 9 4.1-1.4 7-4.8 7-9V6l-7-3Z"/><path d="m9.2 12 2 2 3.6-3.8"/>',
        'settings'  => '<path d="M4 6h10"/><path d="M18 6h2"/><circle cx="16" cy="6" r="2"/><path d="M4 12h4"/><path d="M12 12h8"/><circle cx="10" cy="12" r="2"/><path d="M4 18h9"/><path d="M17 18h3"/><circle cx="15" cy="18" r="2"/>',
        'users'     => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.2 2.7-5.2 6-5.2s6 2 6 5.2"/><path d="M16 5.5a3 3 0 0 1 0 5.8"/><path d="M18 14.4c2 .6 3 2.1 3 4.1"/>',
        'logout'    => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h10"/>',
        'external'  => '<path d="M14 4h6v6"/><path d="m20 4-9 9"/><path d="M19 14v5a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 19V6.5A1.5 1.5 0 0 1 5 5h5"/>',
        'menu'      => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
        'close'     => '<path d="m6 6 12 12"/><path d="M18 6 6 18"/>',
        'plus'      => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'search'    => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'eye'       => '<path d="M2.5 12S6 5.8 12 5.8 21.5 12 21.5 12 18 18.2 12 18.2 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'   => '<path d="M4 4.5 20 20"/><path d="M9.6 6.2A9.6 9.6 0 0 1 12 5.8c6 0 9.5 6.2 9.5 6.2a17 17 0 0 1-3 3.6"/><path d="M6.4 8.1A17 17 0 0 0 2.5 12S6 18.2 12 18.2a9.4 9.4 0 0 0 3.2-.5"/><path d="M10 10a3 3 0 0 0 4 4"/>',
        'arrow-left'=> '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',
        'arrow-right'=>'<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'check'     => '<path d="m5 12.5 4.5 4.5L19 7"/>',
        'alert'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5"/><path d="M12 16.2v.3"/>',
        'copy'      => '<rect x="9" y="9" width="11" height="11" rx="2.5"/><path d="M15 5.5A1.5 1.5 0 0 0 13.5 4H6a2 2 0 0 0-2 2v7.5A1.5 1.5 0 0 0 5.5 15"/>',
        'calendar'  => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M3.5 10h17"/>',
        'wallet'    => '<path d="M20 8V6.5A2.5 2.5 0 0 0 17.5 4H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v9a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 18V6"/><path d="M16.5 12.5h.01"/>',
        'phone'     => '<path d="M6.5 3.5h2.2l1.6 4-1.9 1.4a12 12 0 0 0 5.2 5.2l1.4-1.9 4 1.6v2.2a2.5 2.5 0 0 1-2.7 2.5C9.6 18.1 5.9 14.4 4 7.2A2.5 2.5 0 0 1 6.5 3.5Z"/>',
        'trend'     => '<path d="m4 16 5-5 3.5 3.5L20 7"/><path d="M15 7h5v5"/>',
        'sparkle'   => '<path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3Z"/><path d="M19 16.5 20 19l2.5 1-2.5 1-1 2.5-1-2.5L15.5 20l2.5-1 1-2.5Z"/>',
        'whatsapp'  => '<path d="M20 11.5a8 8 0 0 1-11.7 7.1L4 20l1.5-4.2A8 8 0 1 1 20 11.5Z"/><path d="M9 9.2c.8 2.8 2.6 4.6 5.4 5.4l1-1.5-.2-.9-1.6-.6-.8.7a5.7 5.7 0 0 1-1.7-1.7l.7-.8-.6-1.6-.9-.2-1.3 1.2Z"/>',
        'inbox'     => '<path d="M4 13 6 5h12l2 8v5.5A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5V13Z"/><path d="M4 13h4l1.5 2.5h5L16 13h4"/>',
    ];
    $path = $paths[$name] ?? $paths['sparkle'];
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function admin_pending_count(): int
{
    static $count = null;
    if ($count !== null) {
        return $count;
    }
    $pdo = lk_db();
    if (!$pdo) {
        return $count = 0;
    }
    try {
        return $count = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'baru'")->fetchColumn();
    } catch (Throwable $error) {
        return $count = 0;
    }
}

function admin_nav(): array
{
    return [
        'Utama' => [
            ['key' => 'dashboard', 'label' => 'Ringkasan', 'icon' => 'dashboard', 'href' => 'index.php'],
            ['key' => 'orders',    'label' => 'Pesanan',   'icon' => 'orders',    'href' => 'orders.php', 'badge' => admin_pending_count()],
        ],
        'Konten' => [
            ['key' => 'services',      'label' => 'Layanan & Harga', 'icon' => 'services', 'href' => 'services.php'],
            ['key' => 'promo',         'label' => 'Promo',            'icon' => 'promo',    'href' => 'content.php?tab=promo'],
            ['key' => 'areas',         'label' => 'Area & Slot',      'icon' => 'areas',    'href' => 'content.php?tab=area'],
            ['key' => 'hours',         'label' => 'Jam Operasional',  'icon' => 'hours',    'href' => 'content.php?tab=jam'],
            ['key' => 'testimonials',  'label' => 'Testimoni',        'icon' => 'star',     'href' => 'content.php?tab=testimoni'],
            ['key' => 'guarantees',    'label' => 'Jaminan',          'icon' => 'shield',   'href' => 'content.php?tab=jaminan'],
        ],
        'Sistem' => [
            ['key' => 'settings', 'label' => 'Pengaturan', 'icon' => 'settings', 'href' => 'settings.php'],
            ['key' => 'account',  'label' => 'Akun & Tim', 'icon' => 'users',    'href' => 'account.php'],
        ],
    ];
}

function admin_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? 'A', 0, 1, 'UTF-8');
    $last  = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1, 'UTF-8') : '';
    return mb_strtoupper($first . $last, 'UTF-8');
}

function admin_pill(string $status): string
{
    $meta = lk_order_statuses()[$status] ?? ['label' => ucfirst($status), 'tone' => 'info'];
    return '<span class="pill pill--' . e($meta['tone']) . '"><i></i>' . e($meta['label']) . '</span>';
}

function admin_head(string $title, string $active): void
{
    $user = admin_current();
    $pageTitle = $title;
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle) ?> · GO Laundry CMS</title>
<meta name="theme-color" content="#2A2040">
<link rel="icon" type="image/png" href="../go-laundry.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/tokens.css?v=<?= @filemtime(dirname(__DIR__) . '/assets/tokens.css') ?: 1 ?>">
<link rel="stylesheet" href="assets/admin.css?v=<?= @filemtime(dirname(__DIR__) . '/assets/admin.css') ?: 1 ?>">
</head>
<body>
<a class="skip-link" href="#main">Lewati ke konten</a>
<div class="shell">
    <aside class="sidebar" id="sidebar" aria-label="Menu admin">
        <a class="brand" href="index.php" aria-label="GO Laundry, dashboard">
            <span class="brand__plate">
                <img class="brand__logo" src="../go-laundry.png" alt="GO Laundry" width="1024" height="1024">
            </span>
            <span class="brand__text">
                <span>Admin CMS</span>
            </span>
        </a>

        <nav class="nav">
            <?php foreach (admin_nav() as $group => $items): ?>
                <p class="nav__label"><?= e($group) ?></p>
                <?php foreach ($items as $item): ?>
                    <a class="nav__item<?= $active === $item['key'] ? ' is-active' : '' ?>"
                       href="<?= e($item['href']) ?>"<?= $active === $item['key'] ? ' aria-current="page"' : '' ?>>
                        <?= admin_icon($item['icon'], 18) ?>
                        <span><?= e($item['label']) ?></span>
                        <?php if (!empty($item['badge'])): ?><b class="nav__badge"><?= (int) $item['badge'] ?></b><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar__foot">
            <a class="nav__item nav__item--ghost" href="../index.php" target="_blank" rel="noopener">
                <?= admin_icon('external', 18) ?><span>Lihat situs</span>
            </a>
            <div class="who">
                <span class="who__avatar"><?= e(admin_initials((string) ($user['name'] ?? 'Admin'))) ?></span>
                <span class="who__text">
                    <strong><?= e((string) ($user['name'] ?? 'Admin')) ?></strong>
                    <small><?= e(($user['role'] ?? 'editor') === 'owner' ? 'Pemilik' : 'Editor') ?></small>
                </span>
                <a class="who__out" href="logout.php" aria-label="Keluar"><?= admin_icon('logout', 17) ?></a>
            </div>
        </div>
    </aside>

    <div class="sidebar-backdrop" data-sidebar-close hidden></div>

    <div class="shell__body">
        <header class="topbar">
            <button class="iconbtn" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="sidebar" aria-label="Buka menu">
                <?= admin_icon('menu', 20) ?>
            </button>
            <p class="crumbs"><span>Admin</span><span aria-hidden="true">/</span><strong><?= e($pageTitle) ?></strong></p>
            <div class="topbar__right">
                <a class="topbar__link" href="../index.php" target="_blank" rel="noopener">
                    <?= admin_icon('external', 15) ?><span class="topbar__link-label">Situs</span>
                </a>
                <span class="topbar__date"><?= e(date('d M Y')) ?></span>
            </div>
        </header>

        <main class="page" id="main">
            <?php if (($user['email'] ?? '') === 'admin@golaundry.id'): ?>
            <div class="hintbox" style="margin-bottom:16px">
                <?= admin_icon('shield', 17) ?>
                <span><strong>Akun demo aktif.</strong> Ganti email dan kata sandi lewat menu <a href="account.php">Akun</a> sebelum situs dipakai.</span>
            </div>
            <?php endif; ?>
            <?php $flashes = admin_flashes(); ?>
            <?php if ($flashes !== []): ?>
                <div class="toasts" id="toasts" role="status" aria-live="polite">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="toast toast--<?= e($flash['type']) ?>" data-toast>
                            <span class="toast__icon"><?= admin_icon($flash['type'] === 'success' ? 'check' : 'alert', 17) ?></span>
                            <p><?= e($flash['message']) ?></p>
                            <button type="button" class="toast__close" data-toast-close aria-label="Tutup"><?= admin_icon('close', 14) ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif;
}

function admin_foot(): void
{
    ?>
        </main>
        <footer class="pagefoot">
            <span>GO Laundry CMS</span>
            <span>Data operasional tersimpan di MySQL</span>
        </footer>
    </div>
</div>

<dialog class="confirm" id="confirm-dialog" aria-labelledby="confirm-title">
    <form method="dialog">
        <span class="confirm__icon"><?= admin_icon('alert', 22) ?></span>
        <h2 id="confirm-title">Yakin melanjutkan?</h2>
        <p id="confirm-text">Tindakan ini tidak bisa dibatalkan.</p>
        <div class="confirm__actions">
            <button class="abtn abtn--ghost" value="cancel">Batal</button>
            <button class="abtn abtn--danger" value="confirm">Ya, lanjutkan</button>
        </div>
    </form>
</dialog>
<script src="assets/admin.js" defer></script>
</body>
</html><?php
}

function admin_page_head(string $title, string $subtitle, string $actions = ''): void
{
    ?>
    <div class="page__head">
        <div>
            <h1><?= e($title) ?></h1>
            <p><?= e($subtitle) ?></p>
        </div>
        <?php if ($actions !== ''): ?><div class="page__actions"><?= $actions ?></div><?php endif; ?>
    </div>
    <?php
}

function admin_field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<p class="field__err">' . e($errors[$key]) . '</p>' : '';
}

function admin_ago(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '—';
    }
    $time = strtotime($datetime);
    if ($time === false) {
        return '—';
    }
    $diff = time() - $time;
    if ($diff < 60) {
        return 'baru saja';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' mnt lalu';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' jam lalu';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . ' hari lalu';
    }
    return date('d M Y', $time);
}

function admin_short_money(float $amount): string
{
    if ($amount >= 1000000) {
        return 'Rp ' . rtrim(rtrim(number_format($amount / 1000000, 1, ',', '.'), '0'), ',') . ' jt';
    }
    return rupiah($amount);
}
