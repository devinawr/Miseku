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
    // Jalankan proses otomatis (miseku:tick) saat halaman dibuka, maks. 1x/menit — tanpa perlu schedule:work
    'auto_tick_on_request' => env('MISEKU_AUTO_TICK', true),

    // BPMN 2 — tenggat pelunasan DP sejak tagihan pelunasan terbit (hari)
    'settlement_days' => 14,

    // BPMN 2 — tenggat artis menyerahkan barang ke kurir sejak lunas (hari)
    'shipping_days' => 14,

    // Pengingat H-7, H-3, H-1 (urutan = settlement/shipping_reminder_stage 1,2,3)
    'reminder_days' => [7, 3, 1],

    // Tagihan Midtrans yang tidak dibayar lewat batas ini → kedaluwarsa (jam)
    'payment_expiry_hours' => 24,

    // Auto selesai dihitung dari kedatangan yang diverifikasi, bukan dari tanggal kirim.
    'auto_complete_days' => 3,

    // BPMN 5 — masa retur sejak pesanan selesai/diterima (hari)
    'return_days' => 3,

    // Jendela ulasan sejak completed_at (hari) — kamus data v4.1
    'review_days_buyer'  => 30,
    'review_days_artist' => 15,

    // BPMN 6 — minimum penarikan dana (rupiah)
    'withdrawal_minimum' => 50000,
    // Kalender 2026 adalah default kebijakan Miseku, harus diperbarui tiap tahun.
    'withdrawal_settlement_business_days' => 3,
    // Simulasi HANYA database demo lokal tanpa kredensial Midtrans.
    'demo_settlement' => env('MISEKU_DEMO_SETTLEMENT', false),
    'settlement_holidays' => array_values(array_filter(array_map('trim', explode(',', env('MISEKU_SETTLEMENT_HOLIDAYS', '2026-01-01,2026-01-16,2026-02-16,2026-02-17,2026-03-18,2026-03-19,2026-03-20,2026-03-23,2026-03-24,2026-04-03,2026-05-01,2026-05-14,2026-05-15,2026-05-27,2026-05-28,2026-06-01,2026-06-16,2026-08-17,2026-08-25,2026-12-24,2026-12-25'))))),
    'settlement_calendar_from' => env('MISEKU_SETTLEMENT_CALENDAR_FROM', '2026-01-01'),
    'settlement_calendar_through' => env('MISEKU_SETTLEMENT_CALENDAR_THROUGH', '2026-12-31'),

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
