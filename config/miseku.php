<?php

/*
|--------------------------------------------------------------------------
| Aturan bisnis Miseku (BPMN 1–6)
|--------------------------------------------------------------------------
| Semua angka tenggat/aturan dikumpulkan di sini supaya mudah dijelaskan
| saat sidang dan mudah diubah tanpa menyentuh kode.
| Tarif admin fee & penalti rating TIDAK di sini — itu dari tabel
| platform_setting (diubah admin lewat menu Pengaturan).
*/

return [
    // BPMN 2 — tenggat pelunasan DP sejak tagihan pelunasan terbit (hari)
    'settlement_days' => 14,

    // BPMN 2 — tenggat artis menyerahkan barang ke kurir sejak lunas (hari)
    'shipping_days' => 14,

    // Pengingat H-7, H-3, H-1 (urutan = settlement/shipping_reminder_stage 1,2,3)
    'reminder_days' => [7, 3, 1],

    // Tagihan Midtrans yang tidak dibayar lewat batas ini → kedaluwarsa (jam)
    'payment_expiry_hours' => 24,

    // BPMN 5 — masa retur sejak pesanan selesai/diterima (hari)
    'return_days' => 3,

    // Jendela ulasan sejak completed_at (hari) — kamus data v4.1
    'review_days_buyer'  => 30,
    'review_days_artist' => 15,

    // BPMN 6 — minimum penarikan dana (rupiah)
    'withdrawal_minimum' => 50000,

    // Berat default per item (gram) untuk hitung ongkir — tabel product tidak punya kolom berat
    'default_item_weight' => 200,

    // Kurir yang ditawarkan saat checkout
    'couriers' => [
        'jne'     => 'JNE',
        'pos'     => 'POS Indonesia',
        'tiki'    => 'TIKI',
        'sicepat' => 'SiCepat',
        'jnt'     => 'J&T',
    ],

    // Akun pembeli generik untuk penjualan OTS di booth event
    'ots_buyer_email' => env('MISEKU_OTS_BUYER_EMAIL', 'pembeli-umum@miseku.test'),

    // Kota asal default bila artis belum punya alamat utama (ID kota RajaOngkir / daftar lokal)
    'default_origin_city' => env('MISEKU_DEFAULT_ORIGIN', '151'),
];
