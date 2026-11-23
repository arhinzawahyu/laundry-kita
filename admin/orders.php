<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = admin_require_login();
$pdo = lk_db();
$statuses = lk_order_statuses();
$activeStatuses = ['baru', 'dikonfirmasi', 'dijemput', 'proses', 'siap', 'diantar'];

$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : 'all';
if ($status !== 'all' && $status !== 'aktif' && !isset($statuses[$status])) {
    $status = 'all';
}
$search = isset($_GET['q']) && is_string($_GET['q']) ? clean_input($_GET['q']) : '';

$where = [];
$params = [];
if ($status === 'aktif') {
    $where[] = "status IN ('" . implode("','", $activeStatuses) . "')";
} elseif ($status !== 'all') {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(code LIKE ? OR name LIKE ? OR whatsapp LIKE ? OR desa LIKE ? OR address LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
$whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

$counts = array_fill_keys(array_keys($statuses), 0);
$total = 0;
$activeCount = 0;
if ($pdo) {
    try {
        foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM orders GROUP BY status') as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['c'];
            }
        }
        $total = array_sum($counts);
        foreach ($activeStatuses as $activeStatus) {
            $activeCount += $counts[$activeStatus];
        }
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}

/* CSV export memakai filter yang sama, tanpa pagination. */
if ($pdo && ($_GET['export'] ?? '') === 'csv') {
    try {
        $statement = $pdo->prepare('SELECT * FROM orders' . $whereSql . ' ORDER BY created_at DESC, id DESC');
        $statement->execute($params);
        $rows = $statement->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pesanan-laundry-kita-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Kode', 'Nama', 'WhatsApp', 'Desa', 'Alamat', 'Tanggal jemput', 'Slot', 'Layanan', 'Berat', 'Estimasi', 'Status', 'Dibuat']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['code'],
                $row['name'],
                $row['whatsapp'],
                $row['desa'],
                $row['address'],
                $row['pickup_date'],
                $row['slot'],
                $statuses[$row['status']]['label'] ?? $row['status'],
                $row['weight'],
                $row['estimate_total'],
                $row['status'],
                $row['created_at'],
            ]);
        }
        fclose($out);
        exit;
    } catch (Throwable $error) {
        admin_flash('error', 'Ekspor gagal: ' . $error->getMessage());
        admin_redirect('orders.php');
    }
}

$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$pages = 1;
$orders = [];
$filteredTotal = 0;

if ($pdo) {
    try {
        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM orders' . $whereSql);
        $countStatement->execute($params);
        $filteredTotal = (int) $countStatement->fetchColumn();
        $pages = max(1, (int) ceil($filteredTotal / $perPage));
        $page = min($page, $pages);

        $statement = $pdo->prepare(
            'SELECT * FROM orders' . $whereSql . ' ORDER BY created_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
        );
        $statement->execute($params);
        $orders = $statement->fetchAll();
    } catch (Throwable $error) {
        $GLOBALS['lk_db_error'] = $error->getMessage();
    }
}

$baseQuery = ['status' => $status, 'q' => $search];
function orders_url(array $overrides = []): string
{
    global $baseQuery;
    $query = array_filter(array_merge($baseQuery, $overrides), static fn ($value) => $value !== '' && $value !== null);
    return 'orders.php' . ($query ? '?' . http_build_query($query) : '');
}

admin_head('Pesanan', 'orders');

$actions = '<a class="abtn abtn--ghost" href="' . e(orders_url(['export' => 'csv'])) . '">' . admin_icon('external', 15) . 'Ekspor CSV</a>'
    . '<a class="abtn abtn--primary" href="../index.php#pesan" target="_blank" rel="noopener">' . admin_icon('plus', 15) . 'Form jadwal</a>';
admin_page_head('Pesanan', 'Semua jadwal jemput yang masuk dari halaman utama.', $actions);

if (!$pdo) {
    ?>
    <div class="card"><div class="card__body"><div class="empty">
        <span class="empty__icon"><?= admin_icon('alert', 22) ?></span>
        <strong>Database belum tersambung</strong>
        <p>Nyalakan MySQL lalu muat ulang halaman ini.</p>
    </div></div></div>
    <?php
    admin_foot();
    exit;
}
?>

<section class="card">
    <form class="toolbar" method="get" action="orders.php">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <label class="search">
            <?= admin_icon('search', 16) ?>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari kode, nama, WhatsApp, desa..." aria-label="Cari pesanan">
        </label>
        <button class="abtn abtn--ghost abtn--sm" type="submit">Cari</button>
        <?php if ($search !== ''): ?>
            <a class="abtn abtn--quiet abtn--sm" href="<?= e(orders_url(['q' => ''])) ?>">Reset</a>
        <?php endif; ?>
        <span class="muted" style="font-size:11.5px;margin-left:auto"><?= $filteredTotal ?> hasil</span>
    </form>

    <div class="chips" style="padding:12px 18px 14px;border-bottom:1px solid var(--line)">
        <?php
        $chips = [
            'all'    => ['label' => 'Semua', 'count' => $total],
            'aktif'  => ['label' => 'Aktif', 'count' => $activeCount],
            'baru'   => ['label' => 'Baru', 'count' => $counts['baru']],
            'dikonfirmasi' => ['label' => 'Dikonfirmasi', 'count' => $counts['dikonfirmasi']],
            'selesai'=> ['label' => 'Selesai', 'count' => $counts['selesai']],
            'batal'  => ['label' => 'Batal', 'count' => $counts['batal']],
        ];
        ?>
        <?php foreach ($chips as $key => $chip): ?>
            <a class="chip<?= $status === $key ? ' is-active' : '' ?>" href="<?= e(orders_url(['status' => $key, 'page' => 1])) ?>">
                <?= e($chip['label']) ?><b><?= (int) $chip['count'] ?></b>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($orders === []): ?>
        <div class="empty">
            <span class="empty__icon"><?= admin_icon('inbox', 22) ?></span>
            <strong>Tidak ada pesanan di filter ini</strong>
            <p><?= $search !== '' ? 'Coba kata kunci lain atau reset pencarian.' : 'Pesanan baru akan tampil otomatis setelah formulir dikirim.' ?></p>
        </div>
    <?php else: ?>
        <div class="tablewrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th class="hide-sm">Jadwal</th>
                        <th class="hide-sm">Layanan</th>
                        <th>Status</th>
                        <th class="num">Estimasi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <span class="code"><?= e($order['code']) ?></span>
                                <div class="muted" style="font-size:10.5px;margin-top:3px"><?= e(admin_ago((string) $order['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="cust">
                                    <span class="cust__avatar"><?= e(admin_initials((string) $order['name'])) ?></span>
                                    <span class="cust__text">
                                        <strong><?= e($order['name']) ?></strong>
                                        <small><?= e($order['desa']) ?> · <?= e($order['whatsapp']) ?></small>
                                    </span>
                                </div>
                            </td>
                            <td class="hide-sm">
                                <div style="font-size:12.3px"><?= e(date('d/m/Y', strtotime((string) $order['pickup_date']))) ?></div>
                                <small class="muted"><?= e($order['slot']) ?></small>
                            </td>
                            <td class="hide-sm">
                                <span style="font-size:12.3px"><?= e($order['service_key']) ?></span>
                                <?php if ($order['weight'] !== null): ?>
                                    <small class="muted"> · <?= e(rtrim(rtrim(number_format((float) $order['weight'], 2, ',', '.'), '0'), ',')) ?> kg</small>
                                <?php endif; ?>
                            </td>
                            <td><?= admin_pill((string) $order['status']) ?></td>
                            <td class="num"><?= $order['estimate_total'] !== null ? e(rupiah((float) $order['estimate_total'])) : '<span class="muted">—</span>' ?></td>
                            <td class="num">
                                <a class="abtn abtn--ghost abtn--sm" href="order.php?id=<?= (int) $order['id'] ?>">Buka <?= admin_icon('arrow-right', 12) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:13px 18px;border-top:1px solid var(--line)">
                <span class="muted" style="font-size:11.5px">Halaman <?= $page ?> dari <?= $pages ?></span>
                <div style="display:flex;gap:5px">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <?php if ($i === 1 || $i === $pages || abs($i - $page) <= 1): ?>
                            <a class="chip<?= $i === $page ? ' is-active' : '' ?>" href="<?= e(orders_url(['page' => $i])) ?>" style="justify-content:center;min-width:30px"><?= $i ?></a>
                        <?php elseif (abs($i - $page) === 2): ?>
                            <span class="muted" style="padding:0 4px">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php admin_foot(); ?>
