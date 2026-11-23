<?php
declare(strict_types=1);

/* ----------------------------------------------------------------------------
   5. Server side validation for the pickup form + JSON/HTML response.
   ------------------------------------------------------------------------- */
$formState = [
    'values' => [
        'nama'     => '',
        'whatsapp' => '',
        'desa'     => '',
        'alamat'   => '',
        'tanggal'  => '',
        'slot'     => '',
        'layanan'  => 'kiloan',
        'catatan'  => '',
        'berat'    => '',
    ],
    'errors'  => [],
    'notice'  => '',
    'success' => false,
];

$openAuthOnLoad = false;
$draftSummary   = [];

$restore = $_SESSION['lkj_pickup_draft']['fields'] ?? null;
if (is_array($restore)) {
    foreach ($formState['values'] as $key => $current) {
        if (isset($restore[$key]) && is_string($restore[$key])) {
            $formState['values'][$key] = $restore[$key];
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'jadwal') {
    foreach ($formState['values'] as $key => $current) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $formState['values'][$key] = clean_input($_POST[$key]);
        }
    }

    $values = $formState['values'];
    $now    = time();
    $log    = $_SESSION['lkj_post_log'] ?? [];
    $log    = array_values(array_filter(is_array($log) ? $log : [], static fn ($stamp) => is_int($stamp) && ($now - $stamp) < 600));

    if (clean_input($_POST['website'] ?? '') !== '') {
        if (wants_json()) {
            json_out(['ok' => true, 'needs_auth' => true, 'csrf' => csrf_token(), 'message' => 'Jadwal diterima.']);
        }
        $openAuthOnLoad = true;
    } elseif (count($log) >= 6) {
        $formState['errors']['form'] = 'Terlalu banyak percobaan dalam 10 menit. Tunggu sebentar lalu coba lagi.';
    } elseif (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $formState['errors']['form'] = 'Sesi formulir kedaluwarsa. Muat ulang halaman, lalu isi kembali.';
    } else {
        if (text_len($values['nama']) < 2 || text_len($values['nama']) > 60) {
            $formState['errors']['nama'] = 'Tulis nama penerima, 2 sampai 60 karakter.';
        }

        $wa = preg_replace('/[^0-9+]/', '', $values['whatsapp']) ?? '';
        $waDisplay = $values['whatsapp'];
        if (str_starts_with($wa, '+62')) {
            $wa = '62' . substr($wa, 3);
        } elseif (str_starts_with($wa, '0')) {
            $wa = '62' . substr($wa, 1);
        }
        if (preg_match('/^628[1-9][0-9]{6,11}$/', $wa) !== 1) {
            $formState['errors']['whatsapp'] = 'Nomor WhatsApp belum valid. Contoh: 0812 3456 7890.';
        } else {
            $formState['values']['whatsapp'] = $wa;
        }

        $areaOptions = $siteConfig['areas'];
        $areaOptions[] = 'Luar area';
        if (!in_array($values['desa'], $areaOptions, true)) {
            $formState['errors']['desa'] = 'Pilih desa penjemputan dari daftar.';
        }

        if (text_len($values['alamat']) < 5 || text_len($values['alamat']) > 160) {
            $formState['errors']['alamat'] = 'Tulis alamat lengkap, 5 sampai 160 karakter.';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $values['tanggal']);
        $today = new DateTimeImmutable('today');
        if (!$date || $date->format('Y-m-d') !== $values['tanggal']) {
            $formState['errors']['tanggal'] = 'Pilih tanggal penjemputan.';
        } elseif ($date < $today) {
            $formState['errors']['tanggal'] = 'Tanggal penjemputan tidak boleh di masa lalu.';
        } elseif ($date > $today->modify('+30 days')) {
            $formState['errors']['tanggal'] = 'Jadwal dibuka maksimal 30 hari ke depan.';
        }

        if (!in_array($values['slot'], $siteConfig['pickup_slots'], true)) {
            $formState['errors']['slot'] = 'Pilih slot jam penjemputan.';
        }

        if (!isset($siteConfig['services'][$values['layanan']])) {
            $formState['errors']['layanan'] = 'Pilih jenis layanan.';
        }

        if ($values['berat'] !== '' && (!is_numeric($values['berat']) || (float) $values['berat'] <= 0 || (float) $values['berat'] > 100)) {
            $formState['errors']['berat'] = 'Berat perkiraan harus antara 0,5 dan 100 kg.';
        }

        if ($formState['errors'] === []) {
            $log[] = $now;
            $_SESSION['lkj_post_log'] = array_slice($log, -12);

            $_SESSION['lkj_pickup_draft'] = [
                'fields'  => $formState['values'],
                'created' => time(),
            ];

            $savedOrder = lk_create_order(
                $formState['values'],
                $siteConfig,
                isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : null
            );

            $draftSummary = [
                'Nama'        => $formState['values']['nama'],
                'WhatsApp'    => $waDisplay,
                'Desa'        => $formState['values']['desa'],
                'Tanggal'     => $date->format('d/m/Y'),
                'Slot jemput' => $formState['values']['slot'],
                'Layanan'     => $siteConfig['services'][$formState['values']['layanan']]['label'],
            ];
            if ($savedOrder !== null) {
                $draftSummary['Kode pesanan'] = $savedOrder['code'];
                $account = customer_current();
                if ($account !== null) {
                    customer_attach_order((int) $savedOrder['id'], (int) $account['id']);
                }
            }

            $formState['success'] = true;
            $formState['notice'] = 'Jadwal disimpan. Masuk atau buat akun untuk mengonfirmasi pesanan.';
            $newToken = csrf_rotate();

            if (wants_json()) {
                json_out([
                    'ok'         => true,
                    'needs_auth' => true,
                    'csrf'       => $newToken,
                    'message'    => $formState['notice'],
                    'draft'      => $draftSummary,
                ]);
            }
            $openAuthOnLoad = true;
        } elseif (wants_json()) {
            json_out([
                'ok'      => false,
                'errors'  => $formState['errors'],
                'message' => 'Periksa kembali kolom yang ditandai.',
                'csrf'    => csrf_token(),
            ]);
        }
    }

    if ($formState['errors'] !== [] && wants_json()) {
        json_out([
            'ok'      => false,
            'errors'  => $formState['errors'],
            'message' => $formState['errors']['form'] ?? 'Periksa kembali kolom yang ditandai.',
            'csrf'    => csrf_token(),
        ]);
    }
}
