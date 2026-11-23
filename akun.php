<?php
declare(strict_types=1);

/* ============================================================================
   Halaman akun pelanggan: ringkasan, daftar pesanan, dan klaim pesanan lama.
   ========================================================================= */

date_default_timezone_set('Asia/Jakarta');
$siteConfig = require __DIR__ . '/config/site.php';

require_once __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/customer.php';
require_once __DIR__ . '/includes/account-ui.php';

lk_session_boot();

$customer = customer_current();
if ($customer === null) {
    header('Location: login.php');
    exit;
}

$claimError = '';
$claimCode = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'klaim') {
    if (!csrf_valid(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        $claimError = 'Sesi halaman sudah kedaluwarsa. Coba kirim ulang formulir ini.';
    } else {
        $claimCode = strtoupper(clean_input($_POST['code'] ?? ''));
        $claimWa = clean_input($_POST['whatsapp'] ?? '');
        $claimError = (string) (customer_claim_order($claimCode, $claimWa, $customer) ?? '');
        if ($claimError === '') {
            customer_flash('success', 'Pesanan ' . $claimCode . ' sekarang terhubung ke akun Anda.');
            header('Location: akun.php#pesanan');
            exit;
        }
    }
}

$orders = customer_orders((int) $customer['id']);
$events = customer_order_events(array_map(static fn ($order) => (int) $order['id'], $orders));
$stats = customer_order_stats($orders);
$flashes = customer_flashes();
$csrf = csrf_token();
$biz = $siteConfig['business'];
$statusMap = lk_order_statuses();
$track = customer_status_track();
acct_page_start($siteConfig, 'Pesanan saya', 'Ringkasan pesanan dan status cucian Anda di GO Laundry.', 'akun');
?>
<section class="acct-page">
    <div class="wrap">
        <div class="acct-head">
            <div class="acct-head__text">
                <h1 class="acct-title acct-title--sm">Pesanan saya</h1>
                <p class="acct-lead">
                    <?= e((string) $customer['name']) ?> &middot; <?= e(customer_wa_display((string) $customer['whatsapp'])) ?> &middot; <?= e((string) $customer['email']) ?>
                </p>
            </div>
            <div class="acct-head__actions">
                <a class="acct-btn acct-btn--sm" href="index.php#jadwal">Pesan jemput</a>
            </div>
        </div>

        <?php foreach ($flashes as $flash): ?>
        <?= acct_alert((string) $flash['type'], (string) $flash['message']) ?>
        <?php endforeach; ?>

        <div class="acct-stats">
            <div class="acct-stat">
                <span class="acct-stat__value"><?= (int) $stats['total'] ?></span>
                <span class="acct-stat__label">Total pesanan</span>
            </div>
            <div class="acct-stat">
                <span class="acct-stat__value"><?= (int) $stats['aktif'] ?></span>
                <span class="acct-stat__label">Sedang berjalan</span>
            </div>
            <div class="acct-stat">
                <span class="acct-stat__value"><?= (int) $stats['selesai'] ?></span>
                <span class="acct-stat__label">Selesai</span>
            </div>
        </div>

        <div class="acct-split" id="pesanan">
            <div class="acct-list">
                <div class="acct-list__head">
                    <h2 class="acct-subtitle">Pesanan saya</h2>
                    <p class="acct-hint">Terbaru di atas &middot; maksimal 40 pesanan</p>
                </div>

                <?php if ($orders === []): ?>
                <div class="acct-empty">
                    <p class="acct-empty__kicker">Belum ada pesanan</p>
                    <p class="acct-empty__text">Pesanan yang dibuat dengan nomor WhatsApp akun ini akan muncul di sini otomatis. Sudah pernah memesan sebelum punya akun? Klaim dengan kode pesanan.</p>
                    <a class="acct-btn" href="index.php#jadwal">Atur jadwal jemput</a>
                </div>
                <?php endif; ?>

                <?php foreach ($orders as $order):
                    $status = (string) $order['status'];
                    $currentIndex = $status === 'selesai'
                        ? count($track)
                        : customer_status_index($status);
                    $orderEvents = $events[(int) $order['id']] ?? [];
                    $timeByStatus = [];
                    $lastNote = '';
                    foreach ($orderEvents as $event) {
                        $key = (string) $event['status'];
                        if (!isset($timeByStatus[$key])) {
                            $timeByStatus[$key] = (string) $event['created_at'];
                        }
                        if (trim((string) ($event['note'] ?? '')) !== '') {
                            $lastNote = (string) $event['note'];
                        }
                    }
                    if (!isset($timeByStatus['baru'])) {
                        $timeByStatus['baru'] = (string) $order['created_at'];
                    }
                    ?>
                <article class="order-card" aria-labelledby="order-<?= (int) $order['id'] ?>">
                    <div class="order-card__head">
                        <div>
                            <p class="order-card__code" id="order-<?= (int) $order['id'] ?>"><?= e((string) $order['code']) ?></p>
                            <p class="order-card__meta">
                                <?= e(customer_service_label($siteConfig, (string) $order['service_key'])) ?>
                                <?php if ($order['weight'] !== null): ?>
                                &middot; <?= e(rtrim(rtrim(number_format((float) $order['weight'], 1, ',', '.'), '0'), ',')) ?> kg
                                <?php endif; ?>
                            </p>
                        </div>
                        <?= acct_status_pill($status) ?>
                    </div>

                    <?php if ($status === 'batal'): ?>
                    <p class="order-card__cancel">Pesanan ini dibatalkan. Hubungi outlet bila ingin menjadwal ulang.</p>
                    <?php else: ?>
                    <ol class="order-track" aria-label="Tahap pesanan <?= e((string) $order['code']) ?>">
                        <?php foreach ($track as $index => $stepKey):
                            $step = $statusMap[$stepKey] ?? ['label' => ucfirst($stepKey), 'desc' => ''];
                            $isDone = $index < $currentIndex;
                            $isCurrent = $index === $currentIndex;
                            $stateClass = $isDone ? ' is-done' : ($isCurrent ? ' is-current' : '');
                            ?>
                        <li class="order-track__item<?= $stateClass ?>"<?= $isCurrent ? ' aria-current="step"' : '' ?>>
                            <span class="order-track__dot" aria-hidden="true"></span>
                            <span class="order-track__body">
                                <span class="order-track__label"><?= e((string) $step['label']) ?></span>
                                <span class="order-track__time">
                                    <?php if (isset($timeByStatus[$stepKey]) && ($isDone || $isCurrent)): ?>
                                    <?= e(acct_date($timeByStatus[$stepKey], 'd M · H:i')) ?>
                                    <?php elseif ($isCurrent): ?>
                                    Sekarang
                                    <?php elseif ($isDone): ?>
                                    Selesai
                                    <?php else: ?>
                                    Menunggu
                                    <?php endif; ?>
                                </span>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php endif; ?>

                    <?php if ($lastNote !== ''): ?>
                    <p class="order-card__note"><span>Catatan outlet</span> <?= e($lastNote) ?></p>
                    <?php endif; ?>

                    <div class="order-card__details">
                        <?= acct_leader_row('Jemput', acct_date((string) $order['pickup_date']) . ', ' . (string) $order['slot']) ?>
                        <?= acct_leader_row('Daerah', (string) $order['desa']) ?>
                        <?= acct_leader_row(
                            'Estimasi',
                            $order['estimate_total'] !== null
                                ? rupiah((float) $order['estimate_total'])
                                : 'Setelah penimbangan'
                        ) ?>
                        <?= acct_leader_row('Dibuat', acct_date((string) $order['created_at'], 'd M Y')) ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <aside class="acct-aside">
                <div class="acct-panel">
                    <h2 class="acct-subtitle acct-subtitle--sm">Klaim pesanan lama</h2>
                    <p class="acct-hint">Sudah pernah memesan sebelum punya akun? Masukkan kode pesanan dari pesan konfirmasi. Nomor WhatsApp harus sama dengan yang dipakai saat memesan.</p>

                    <?php if ($claimError !== ''): ?>
                    <?= acct_alert('error', $claimError) ?>
                    <?php endif; ?>

                    <form class="acct-form acct-form--tight" method="post" action="akun.php" novalidate>
                        <input type="hidden" name="action" value="klaim">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                        <?php acct_field('code', 'Kode pesanan', [
                            'value' => $claimCode,
                            'error' => '',
                            'attrs' => [
                                'placeholder' => 'LK-260110-A1B2',
                                'maxlength'   => 24,
                                'autocomplete' => 'off',
                                'autocapitalize' => 'characters',
                            ],
                        ]); ?>

                        <?php acct_field('whatsapp', 'Nomor WhatsApp pesanan', [
                            'type'  => 'tel',
                            'value' => customer_wa_display((string) $customer['whatsapp']),
                            'attrs' => [
                                'inputmode'    => 'tel',
                                'maxlength'    => 20,
                                'autocomplete' => 'tel',
                            ],
                        ]); ?>

                        <button class="btn-line acct-submit acct-submit--line" type="submit">Klaim pesanan</button>
                    </form>
                </div>

                <div class="acct-panel acct-panel--deep">
                    <h2 class="acct-subtitle acct-subtitle--sm">Butuh bantuan?</h2>
                    <p class="acct-hint acct-hint--deep">Tim outlet bisa mengecek pesanan Anda langsung dari nama dan nomor WhatsApp.</p>
                    <?php
                    $waNumber = preg_replace('/[^0-9]/', '', (string) $biz['whatsapp']) ?? '';
                    if (!is_placeholder($biz['whatsapp']) && strlen($waNumber) >= 9):
                        $msg = 'Halo ' . $biz['name'] . ', saya ingin menanyakan pesanan akun ' . $customer['email'] . '.';
                        ?>
                    <a class="btn-line acct-deep-link" href="https://wa.me/<?= e($waNumber) ?>?text=<?= e(rawurlencode($msg)) ?>" target="_blank" rel="noopener">Chat outlet lewat WhatsApp</a>
                    <?php else: ?>
                    <p class="acct-hint acct-hint--deep">Nomor WhatsApp outlet belum diisi di pengaturan.</p>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</section>
<?php acct_page_end($siteConfig); ?>
