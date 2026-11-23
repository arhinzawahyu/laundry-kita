<?php
declare(strict_types=1);

/* Central configuration. Replace bracketed values with real business data. */
return [
    'business' => [
        'name'      => 'GO Laundry',
        'tagline'   => 'Laundry jemput antar untuk Delanggu dan Klaten',
        'region'    => 'Kecamatan Delanggu, Kabupaten Klaten, dan sekitarnya',
        'address'   => '[ALAMAT_OUTLET]',
        'whatsapp'  => '[NOMOR_WHATSAPP]',
        'email'     => '[EMAIL_OUTLET]',
        'instagram' => '[INSTAGRAM_OUTLET]',
        'maps_url'  => null,
    ],
    'auth' => [
        'login_url'     => 'login.php',
        'register_url'  => 'register.php',
        'backend_ready' => null,
    ],
    'meta' => [
        'title'       => 'GO Laundry | Jemput antar cucian Delanggu, Klaten',
        'description' => 'Pesan jemput cucian di Delanggu, Klaten. Hitung estimasi harga, pilih layanan kiloan, satuan, atau ekspres, lalu atur jadwal penjemputan.',
    ],
    'sequence_folder' => 'sequence',
    'sequence_limit'  => 240,
    'navigation' => [
        ['id' => 'layanan',          'label' => 'Layanan'],
        ['id' => 'kalkulator',       'label' => 'Kalkulator'],
        ['id' => 'cara-kerja',       'label' => 'Cara kerja'],
        ['id' => 'tracking-preview', 'label' => 'Pelacakan'],
        ['id' => 'membership',       'label' => 'Poin'],
        ['id' => 'jangkauan',        'label' => 'Jangkauan'],
    ],
    'services' => [
        'kiloan' => [
            'label'          => 'Kiloan',
            'unit'           => 'kg',
            'price'          => 7000,
            'min_qty'        => 3,
            'step'           => 0.5,
            'duration_hours' => 48,
            'duration_label' => '2 hari',
            'blurb'          => 'Dicuci per kilogram untuk cucian harian keluarga.',
            'detail'         => 'Cuci, kering, lipat. Pakaian putih dipisah dari yang berwarna.',
        ],
        'satuan' => [
            'label'          => 'Satuan',
            'unit'           => 'potong',
            'price'          => 10000,
            'min_qty'        => 1,
            'step'           => 1,
            'duration_hours' => 72,
            'duration_label' => '3 hari',
            'blurb'          => 'Dihitung per potong untuk item yang perlu penanganan khusus.',
            'detail'         => 'Tarif per jenis barang berbeda. Kurir mencatat jenis dan jumlah di tiket.',
        ],
        'ekspres' => [
            'label'          => 'Ekspres',
            'unit'           => 'kg',
            'price'          => 12000,
            'min_qty'        => 3,
            'step'           => 0.5,
            'duration_hours' => 8,
            'duration_label' => '8 jam',
            'blurb'          => 'Untuk cucian yang harus selesai di hari yang sama.',
            'detail'         => 'Kuota slot ekspres terbatas dan dikonfirmasi kurir saat penjemputan.',
        ],
    ],
    'promo' => [
        'first_order_percent' => 50,
        'free_pickup_area'    => 'Delanggu',
        'member_voucher'      => '[NILAI_VOUCHER_MEMBER]',
        'note'                => 'Besaran promo pada halaman ini masih contoh dan menunggu konfirmasi pemilik.',
    ],
    'membership' => [
        'earn' => [
            'points_per_1000_rupiah' => 1,
            'points_per_kg'          => 0,
        ],
        'tiers' => [
            [
                'key'        => 'baru',
                'name'       => 'Pelanggan baru',
                'min_points' => 0,
                'benefits'   => ['Poin dari transaksi pertama', 'Voucher selamat datang setelah akun aktif'],
            ],
            [
                'key'        => 'setia',
                'name'       => 'Pelanggan setia',
                'min_points' => 100,
                'benefits'   => ['Poin 1,25x dari setiap transaksi', 'Prioritas slot jemput akhir pekan'],
            ],
            [
                'key'        => 'rumah',
                'name'       => 'Rumah tangga',
                'min_points' => 500,
                'benefits'   => ['Poin 1,5x dari setiap transaksi', 'Gratis antar untuk satu pesanan per bulan'],
            ],
        ],
        'redeem' => [
            ['cost' => 100, 'value' => 'Potongan Rp 10.000'],
            ['cost' => 250, 'value' => 'Gratis 1 kg cucian kiloan'],
            ['cost' => 600, 'value' => 'Gratis antar untuk satu pesanan'],
        ],
        'note' => 'Rancangan program dan angka poin masih placeholder, menunggu konfirmasi pemilik.',
    ],
    'areas' => [
        'Delanggu', 'Sabrang', 'Gatak', 'Kepanjen', 'Bowan',
        'Sribit', 'Wonosari', 'Ceper', 'Juwiring',
    ],
    'pickup_slots' => [
        '08.00 - 10.00',
        '10.00 - 12.00',
        '13.00 - 15.00',
        '16.00 - 18.00',
    ],
    'hours' => [
        'outlet' => [
            ['day' => 'Senin sampai Sabtu', 'time' => '[JAM_OUTLET_SENIN_SABTU]'],
            ['day' => 'Minggu',             'time' => '[JAM_OUTLET_MINGGU]'],
        ],
        'courier' => [
            ['day' => 'Senin sampai Sabtu', 'time' => '[SLOT_KURIR_SENIN_SABTU]'],
            ['day' => 'Minggu',             'time' => '[SLOT_KURIR_MINGGU]'],
        ],
    ],
    'stats' => [
        ['label' => 'Rating rata-rata', 'value' => '[Placeholder: 4,9/5 dari XXX ulasan]'],
        ['label' => 'Pesanan selesai',  'value' => '[Placeholder: X.XXX pesanan]'],
        ['label' => 'Pelanggan aktif',  'value' => '[Placeholder: XXX pelanggan]'],
    ],
    'testimonials' => [
        [
            'name'    => '[Nama Pelanggan - Desa Kepanjen]',
            'service' => 'Kiloan',
            'quote'   => '[Placeholder ulasan: tulis kutipan asli setelah mendapat izin pelanggan.]',
        ],
        [
            'name'    => '[Nama Pelanggan - Desa Sabrang]',
            'service' => 'Ekspres',
            'quote'   => '[Placeholder ulasan: tulis kutipan asli setelah mendapat izin pelanggan.]',
        ],
        [
            'name'    => '[Nama Pelanggan - Desa Bowan]',
            'service' => 'Satuan',
            'quote'   => '[Placeholder ulasan: tulis kutipan asli setelah mendapat izin pelanggan.]',
        ],
    ],
    'guarantees' => [
        ['title' => 'Anti tertukar', 'text' => 'Setiap pesanan memakai kode tiket dan ditimbang di depan pelanggan.'],
        ['title' => 'Higienis', 'text' => 'Pakaian putih dipisah dari berwarna dan mesin dibersihkan pada jadwal tetap.'],
        ['title' => 'Garansi cuci ulang', 'text' => '[Placeholder: sebutkan syarat, batas waktu, dan cara klaim garansi.]'],
    ],
    'legal' => [
        'privacy' => '[URL_KEBIJAKAN_PRIVASI]',
        'terms'   => '[URL_SYARAT_LAYANAN]',
    ],
];
