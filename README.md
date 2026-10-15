# Miseku — Marketplace Fan Merchandise

Project **Laravel 13 + Livewire 3 + Tailwind v4** untuk TA *"Pembuatan Sistem Informasi
Marketplace Fan Merchandise bagi Artis Independen di Indonesia"*.

Versi ini sudah **sistem lengkap end-to-end**: checkout → pembayaran (Midtrans / simulasi) →
produksi PO & pelunasan DP → pengiriman (RajaOngkir / ongkir lokal) atau ambil di booth event (QR) →
selesai → ulasan dua arah, plus pembatalan, retur, refund, saldo & tarik dana artis, custom
merchandise, voting desain, komunitas fandom, chat, notifikasi, penjualan OTS, tema per fandom, dan panel admin.

Yang tidak ikut di zip cuma `vendor/` dan `node_modules/` (install di komputermu sendiri).
**Baru di versi ini: tema tampilan per fandom** (otomatis dari fandom favorit, bisa dipilih sendiri atau dimatikan, dikelola admin). **Baru juga (hasil survei): urutan beranda** (Banner promo/event → Rekomendasi untukku → Produk terbaru → Pre-order segera ditutup → Pilihan fandom) dan **mode gelap / terang**.

**Baru di versi ini (hasil konsul):** daftar hanya sebagai pembeli (buka toko dari Akun), halaman **Rekomendasi untukmu** (`/rekomendasi`), **kirim foto** di chat & komunitas, **alamat toko** artis (asal ongkir), stepper pesanan rapi, **mode studio** dengan tombol kembali ke mode pembeli, **panel admin terpisah** (admin tidak bisa belanja), **detail penarikan + alasan ditolak**, palet cepat tema, dan **PWA bisa dipasang**.
Panduan Midtrans / RajaOngkir / PWA: `docs/PANDUAN_MIDTRANS_RAJAONGKIR_PWA.md`. Rangkuman konsul: `docs/Rangkuman_Konsul.md`.

> **Update DB dari versi sebelumnya (fitur laporan komunitas):** jalankan `database/sql/PATCH_community_report.sql` di tab **SQL** phpMyAdmin (tabel baru `community_report` + tipe notifikasi `community`), atau impor ulang `database/sql/SQL_FIX.sql` (38 tabel) + `php artisan db:seed`. Pastikan `.env` berisi `APP_LOCALE=id` (pesan validasi bahasa Indonesia di `lang/id/validation.php`).

---

## Teknologi

| Lapisan | Teknologi |
| --- | --- |
| Bahasa / framework | PHP 8.3+ · Laravel 13 |
| UI dinamis | Livewire 3 (+ Alpine.js bawaan) · Tailwind CSS v4 (Vite) |
| Database | MySQL / MariaDB (XAMPP) — struktur dari `database/sql/ERD_FIX_SQL_final.sql` |
| PWA | `public/manifest.webmanifest` + Service Worker |
| QR | `qrcode` (membuat QR pick-up) · `jsqr` (scan lewat kamera di Studio) |
| Pembayaran | Midtrans Snap (opsional) — tanpa key otomatis **mode simulasi** |
| Ongkir | RajaOngkir Komerce (opsional) — tanpa key memakai **tabel kota lokal** `App\Support\Cities` |
| Notifikasi | tabel `track_transaksi` (lonceng di header) + email (default `MAIL_MAILER=log` → `storage/logs/laravel.log`) |
| Otomasi tenggat | perintah `php artisan miseku:tick` (dijadwalkan tiap 15 menit) |

---

## 1. Langkah menjalankan

1. **Extract** zip ke `C:\xampp\htdocs\miseku` (tutup VS Code dulu kalau folder lama mau dihapus).
2. **Dependency PHP**: `composer install`
3. **.env** — sudah ikut di zip (kalau tidak terbawa: `copy .env.example .env`), lalu `php artisan key:generate`.
   Opsional (kosong = mode simulasi):
   ```dotenv
   MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
   MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
   MIDTRANS_IS_PRODUCTION=false
   RAJAONGKIR_API_KEY=xxxx
   ```
   Setelah mengubah `.env` jalankan `php artisan config:clear`.
4. **Database** — nyalakan Apache + MySQL di XAMPP → phpMyAdmin:
   1. `DROP DATABASE marketplace_fanmerch` (kalau sudah ada), buat lagi `marketplace_fanmerch` (`utf8mb4_unicode_ci`).
   2. Tab **Import** → `database/sql/ERD_FIX_SQL_final.sql` → **Go** (37 tabel).
      Kalau tidak mau mengulang database (data lama mau dipertahankan), cukup jalankan
      `database/sql/PATCH_theming.sql` di tab **SQL** — menambah tabel `fandom_theming` + 2 kolom di `user`.
   3. `php artisan db:seed`
   > **Jangan `php artisan migrate`.** Struktur tabel datang dari file SQL.
5. **Frontend** (ada paket baru `qrcode` & `jsqr`): `npm install` lalu `npm run build`
   (saat mengembangkan boleh `npm run dev` di terminal terpisah).
6. **Bersihkan cache view**: `php artisan view:clear`
7. **Jalankan**: `php artisan serve` → buka `http://localhost:8000`
8. **Otomasi tenggat** (pengingat & batal otomatis), pilih salah satu:
   - terminal lain: `php artisan schedule:work`
   - manual saat demo: `php artisan miseku:tick`
   - simulasi waktu maju: `php artisan miseku:tick --now="2026-12-31 10:00"`

> **Event contoh memakai tanggal relatif hari seeding** (Comifuro 20 = kemarin s.d. besok).
> Kalau demo beberapa hari kemudian, ulangi langkah 4 supaya event masih "berlangsung".

### Midtrans (kalau key diisi)

- Halaman bayar `/bayar/{id}` membuka **Snap** (QRIS, e-wallet, VA).
- Webhook: set *Payment Notification URL* di dashboard Midtrans ke
  `https://<domain-publik>/midtrans/notification` (di lokal pakai ngrok/expose).
  Tanpa webhook pun bisa: tombol **"Saya sudah bayar — cek status"** menarik status dari API Midtrans.
- Refund: kanal yang mendukung API direfund otomatis; yang tidak (mis. VA) masuk antrean
  **Admin → Transaksi & refund → Refund** untuk ditransfer manual + upload bukti.

### Mode simulasi (key kosong)

- Halaman bayar menampilkan tombol **"Bayar … (simulasi berhasil)"** dan **"Simulasikan gagal"**.
- Ongkir dihitung dari jarak kota asal artis (alamat utama artis) ke kota tujuan; kurir JNE/POS/TIKI/SiCepat/J&T, layanan REG & EXP.

---

## 2. Akun demo & isi data contoh

Semua password: **`password`**

| Peran | Email | Kondisi menarik untuk dicek |
| --- | --- | --- |
| Admin | `admin@miseku.test` | 1 penarikan menunggu (Kirara), refund PO gagal, event, pengaturan fee |
| Artis | `kirara@miseku.test` | pengajuan batal dari Rani, PO Tote Bag (1 menunggu pelunasan, 1 siap kirim), 2 pesanan pick-up Comifuro 20, voting berjalan, penarikan diajukan |
| Artis | `nakamura@miseku.test` | pesanan dibayar perlu dikirim, **retur** dari Bagas, custom sedang diproduksi, voting selesai → produk PO Art Print Gojo, PO Badge Set gagal target |
| Artis | `hanabi@miseku.test` | penjualan OTS di booth C-21, penarikan selesai, PO Plushie (DP) |
| Pembeli | `buyer1@miseku.test` (Devina) | **tema otomatis** dari fandom favorit (Genshin + Project Sekai, bergiliran per hari); semua status: belum bayar, dibayar, dikirim, selesai (bisa ulasan & retur 3 hari), PO DP, **menunggu pelunasan**, **QR pick-up**, custom dengan 2 penawaran, anggota komunitas Genshin, chat dengan Kirara, 2 alamat |
| Pembeli | `buyer2@miseku.test` (Rani) | **tema dipilih sendiri** (Miku Teal · Project Sekai, padahal favoritnya Jujutsu Kaisen); pengajuan pembatalan, PO DP sudah lunas & dikirim, custom sedang diproduksi |
| Pembeli | `buyer3@miseku.test` (Bagas) | **tema dimatikan** (tampilan default); retur diajukan, QR pick-up (dipakai uji scan), permintaan custom tanpa penawaran |
| Pembeli | `buyer4@miseku.test` (Nadia) | PO full siap dikirim, PO poster menunggu PO ditutup |
| Pembeli | `buyer5`–`buyer8@miseku.test` | belum punya fandom favorit → uji onboarding/cold start; buyer6 & 7 kena refund PO gagal |
| Sistem | `pembeli-umum@miseku.test` | akun "Pembeli Umum (Event)" untuk pesanan OTS (tidak dipakai login) |

Isi lain: 3 toko, 25 produk, batch PO (buka/selesai/gagal), ±40 pesanan selesai lengkap dengan
pembayaran + mutasi saldo (ditahan → dilepas, dipotong admin fee 5% min Rp1.000), ulasan dua arah,
alamat semua user, 3 event (lalu/berlangsung/akan datang) + booth, 2 voting, 3 permintaan custom,
komunitas (anggota, topik, balasan, 1 laporan menunggu), chat, notifikasi.

---

## 3. Langkah cek fitur per peran

### A. Tamu
1. Beranda → katalog (filter) → detail produk (galeri, rating, produk serupa).
2. **Voting desain** `/voting` (harus login untuk memilih) · **Komunitas** `/komunitas` (baca saja).
3. Daftar akun baru → otomatis ke **onboarding** fandom favorit (mode fokus tanpa menu).

### B. Pembeli (`buyer1@miseku.test`)
1. **Alamat**: menu akun → Alamat → tambah (cari kota), set utama.
2. **Beli ready stock**: produk → *Beli sekarang* → checkout (alamat, kurir & layanan, ongkir otomatis) → *Buat pesanan & bayar* → *Bayar (simulasi berhasil)* → status **Dibayar** + notifikasi di lonceng.
3. **Keranjang multi-toko**: produk dari 2 toko + 1 produk PO → checkout → terbentuk beberapa pesanan → tab *Belum bayar*.
4. **PO skema DP**: produk PO ber-DP (mis. Plushie) → pilih *DP* → bayar DP + ongkir.
5. **Pelunasan**: pesanan PO Tote Bag *Menunggu pelunasan* → *Bayar Pelunasan* (tenggat 14 hari; ada *Terbitkan ulang tagihan pelunasan* bila tagihan kedaluwarsa).
6. **Konfirmasi diterima**: pesanan *Dikirim* → *Pesanan diterima* → selesai, dana dilepas ke artis.
7. **Ulasan**: pesanan selesai → rating toko + rating per produk (≤30 hari).
8. **Retur**: pesanan selesai (≤3 hari) → alasan + foto → *Kirim pengajuan retur*.
9. **Pembatalan**: pesanan dibayar → *Ajukan pembatalan* → menunggu keputusan artis.
10. **Pick-up event**: pesanan *Siap diambil di booth* → QR tampil.
11. **Custom**: menu Custom → buat permintaan → lihat penawaran → tolak / pilih & checkout.
12. **Voting**: `/voting` → pilih 1 opsi (1 akun 1 suara).
13. **Komunitas**: gabung fandom → buat topik → balas → lampirkan produk/artis. Hapus pesan/topik milik sendiri; konten orang lain → **Laporkan** (pilih alasan) → masuk antrean admin.
14. **Chat**: ikon chat di header atau tombol *Chat artis* di produk/pesanan.
15. **Notifikasi**: lonceng (polling 30 detik) → halaman Notifikasi (filter + *produk baru sesuai minat*).
16. **Buka toko**: Akun → *Buka toko* → jadi artis.

### C. Artis (`kirara@miseku.test` / `nakamura@miseku.test`)
1. **Studio → Produk**: upload / ubah / sembunyikan / hapus; produk PO isi batch (harga PO, DP, min, slot, tutup, estimasi).
2. **Pesanan** (tab *Perlu tindakan*): kirim (kurir + resi → *Simpan resi & kirim*) atau *Siap diambil di booth* untuk pick-up.
3. **Keputusan batal**: pesanan Rani → *Setujui* / *Tolak*.
4. **Retur** (Nakamura): pesanan Bagas → *Setujui retur* → setelah barang kembali *Diterima, refund* / *Tidak sesuai*.
5. **Batch PO**: tutup PO → target tercapai → *Mulai produksi* → *Produksi selesai* (DP ditagih pelunasan; full → siap kirim). Target tidak tercapai → batal + refund otomatis.
6. **Custom**: beri penawaran harga + estimasi; kerjakan pesanan custom.
7. **Voting**: buat voting 2–6 opsi gambar → selesaikan → *Buka PO desain pemenang*.
8. **Event & booth**: daftar booth (nomor, jam, hari hadir).
9. **Scan QR**: kamera / ketik kode (lihat daftar "Menunggu diambil") → *Validasi* → selesai.
10. **Jual OTS**: pilih booth event berlangsung → isi jumlah → catat penjualan.
11. **Saldo**: saldo tersedia/tertahan + mutasi → tarik dana (min Rp50.000).
12. **Ulas pembeli**: pesanan selesai → rating pembeli (≤15 hari).

### D. Admin (`admin@miseku.test`)
1. **Ringkasan**: pengguna, toko, produk, pesanan berjalan, GMV, pendapatan admin fee, dana tertahan, saldo artis + antrean tindakan.
2. **Persetujuan** usulan fandom/karakter/jenis.
3. **Pengguna**: nonaktifkan / aktifkan akun.
4. **Produk**: sembunyikan / tayangkan.
5. **Transaksi & refund**: pesanan, pembayaran, antrean refund manual → upload bukti.
6. **Penarikan dana**: proses → selesai (upload bukti) atau tolak (saldo kembali).
7. **Event**: tambah/ubah/hapus.
8. **Tema fandom** (`/admin/tema`): tambah/ubah tema per fandom — warna utama, gradasi, aksen, banner; pratinjau + cek kontras; aktif/nonaktif; hapus.
9. **Komunitas** (`/admin/komunitas`): tab *Laporan* (menunggu / dihapus / ditolak) → *Hapus konten* (wajib alasan → penulis & pelapor dapat notifikasi, foto ikut terhapus) atau *Tolak laporan*; tab *Semua topik* untuk memantau & menghapus langsung.
10. **Pengaturan & otomasi**: admin fee, minimum fee, penalti; *Hitung ulang semua rating*; otomasi *Jalankan sekarang*.

### E. Tema tampilan (theming per fandom)
1. Masuk `buyer1` → beranda: band tema di bawah banner + seluruh warna (tombol, tautan, gradasi) mengikuti fandom favorit.
2. Masuk `buyer2` → tema Miku Teal walaupun favoritnya Jujutsu Kaisen (dipilih sendiri). `buyer3` → warna default Miseku.
3. Ikon **kuas** di header → pilih *Otomatis*, salah satu tema, atau *Matikan tema*. Halaman dimuat ulang dengan warna baru.
   Pengaturan lengkap + pratinjau ada di **Akun → Tampilan**.
4. Tamu juga bisa memilih tema (disimpan di session); tamu mode otomatis = default karena belum punya fandom favorit.
5. Admin menonaktifkan / menghapus tema → pengguna yang memakainya kembali ke tema otomatis.
6. **Mode gelap**: ikon kuas → bagian *Mode* (Terang / Gelap / Sistem), atau **Akun → Tampilan → Mode terang / gelap**. Berlaku langsung tanpa muat ulang, disimpan di perangkat (localStorage), default terang. Warna tema fandom tetap dipakai di mode gelap.

### F. Uji tenggat otomatis
```powershell
php artisan miseku:tick --now="<tanggal 2 hari lagi>"   # tagihan kedaluwarsa, pick-up hangus setelah event
php artisan miseku:tick --now="<tanggal 20 hari lagi>"  # PO ditutup, voting selesai, DP hangus, batal otomatis lewat tenggat kirim
```
Ulangi langkah 4 untuk kembali ke data awal.

---

## 4. Fitur yang dikerjakan di versi ini

- Checkout split per artis (RS 1 pesanan, tiap item PO 1 pesanan), alamat/kurir/layanan atau pick-up booth, skema DP/full.
- Pembayaran Midtrans Snap + webhook + cek status; mode simulasi; kedaluwarsa 24 jam; terbit ulang tagihan pelunasan.
- Dana ditahan → dilepas saat selesai (dipotong admin fee), hangus ke artis, refund (API atau manual).
- Alur PO: tutup batch, cek target minimum, produksi, pelunasan DP, kirim.
- Kirim + resi, konfirmasi diterima; pick-up booth dengan QR + scan kamera.
- Pembatalan (pembeli ajukan → artis putuskan; artis batalkan; batal otomatis) + penalti rating.
- Retur (≤3 hari, foto → setujui → cek barang → refund).
- Ulasan dua arah + rating berpenalti.
- Custom merchandise, voting desain → PO, komunitas fandom, chat, notifikasi in-app + email.
- **Banner beranda = gambar jadi** (2 per layar di desktop, seperti banner toko hobi) + **kartu fandom bergambar**.
  Gambar sekarang placeholder dari foto produk contoh — ganti file di `public/img/banner/` (1200×660) dan
  `public/img/fandom/` (480×360) dengan nama yang sama. Daftar nama file: `public/img/banner/README.txt`.
- **Urutan beranda sesuai survei**: 1) Banner promo/event, 2) Rekomendasi untukku, 3) Produk terbaru, 4) Pre-order segera ditutup, 5) Pilihan fandom, lalu Toko artis.
- **Mode gelap / terang / ikuti perangkat** (hasil survei) — CSS di akhir `resources/css/app.css` (blok *MODE GELAP*), skrip `resources/views/partials/color-mode.blade.php`, tombol `components/color-mode-switch.blade.php`. **Wajib `npm run build`.**
- **Tema per fandom (baru)**: tabel `fandom_theming`, mode otomatis / pilih sendiri / matikan, kelola tema di panel admin.
- **Beli sekarang** langsung ke checkout (+ pilih DP/lunas di detail produk), tombol **Checkout** di keranjang aktif, tombol **Chat artis** di detail produk.
- **PO dari desain pemenang** voting: form produk terisi sebagai PO, pemilih voting dapat notifikasi; produk baru diumumkan ke penggemar fandom-nya.
- Header lengkap: ikon tema, chat, notifikasi, menu akun per peran; onboarding tampil mode fokus.
- Event & booth, penjualan OTS, saldo & tarik dana, panel admin lengkap, alamat, buka toko, otomasi `miseku:tick`.

- **Daftar = pembeli saja.** Artis daftar biasa lalu **Akun → Buka toko** (wajib isi alamat toko).
- **Rekomendasi untukmu** (`/rekomendasi`): hanya hasil CBF, tombol "Muat lebih banyak"; "Lihat semua" di beranda menuju halaman ini.
- **Foto di chat & komunitas**: maks. 4 foto (≤4 MB) per pesan/balasan/topik, disimpan di tabel `file_upload` (`source_table` = `chat_message` / `topic_message`), folder `public/uploads/chat` & `public/uploads/komunitas`; klik foto → lightbox.
- **Alamat toko**: disimpan di tabel `address` dengan label `Alamat toko` (tanpa ubah ERD), tidak muncul di daftar alamat pengiriman; jadi asal ongkir.
- **Mode studio**: baris kategori belanja disembunyikan, chip "Studio artis" + tombol **Beranda** (kembali ke mode pembeli).
- **Admin hanya di panel**: middleware `KeepAdminInPanel` — admin yang membuka beranda/keranjang dialihkan ke `/admin`.
- **Penarikan dana**: admin pilih **Selesai** (wajib unggah bukti transfer) atau **Tolak** (wajib alasan). Artis klik riwayat penarikan di **Saldo** → detail (rekening, waktu, diproses oleh, alasan ditolak, bukti transfer).
- **Tema admin**: "Palet cepat" 8 preset warna.
- **PWA**: tombol **Pasang aplikasi** (menu akun, menu HP, banner beranda HP), ikon maskable, shortcut — lihat `docs/PANDUAN_MIDTRANS_RAJAONGKIR_PWA.md`.

---

## 5. Daftar seluruh fitur marketplace

| Modul | Fitur | Status |
| --- | --- | --- |
| Akun | daftar, masuk, onboarding fandom favorit, profil, kata sandi, alamat, buka toko | ✅ |
| Katalog | beranda + rekomendasi CBF, katalog & filter, pencarian, detail, produk serupa, halaman toko | ✅ |
| Belanja | keranjang, checkout split, ongkir, pick-up event, DP/full | ✅ |
| Pembayaran | Midtrans/simulasi, pelunasan, kedaluwarsa, refund | ✅ |
| Pesanan | status lengkap, resi, konfirmasi, QR pick-up, batal, retur, ulasan dua arah | ✅ |
| Pre-order | batch, target minimum, produksi, pelunasan, PO gagal → refund | ✅ |
| Custom | permintaan, penawaran, pilih → checkout, produksi | ✅ |
| Voting desain | buat, pilih, hasil, PO dari pemenang | ✅ |
| Event | event (admin), booth (artis), pick-up QR, OTS | ✅ |
| Komunitas | anggota, topik, balasan, laporan & moderasi admin | ✅ |
| Komunikasi | chat, notifikasi in-app & email | ✅ |
| Keuangan | saldo ditahan/tersedia, admin fee, tarik dana, mutasi | ✅ |
| Admin | ringkasan, persetujuan, pengguna, produk, transaksi, refund, penarikan, event, pengaturan | ✅ |
| Tampilan | theming per fandom (otomatis dari fandom favorit / pilih sendiri / matikan, dikelola admin) | ✅ |

---

## 6. Keputusan desain yang perlu dikonfirmasi

1. **DP + ongkir dibayar di awal**; pelunasan = sisa harga. Tenggat pelunasan 14 hari sejak produksi selesai.
2. **Tenggat kirim 14 hari**: RS sejak dibayar; PO full/custom sejak produksi selesai; PO DP sejak lunas. Pick-up mengikuti tanggal event.
3. **Tidak ada auto-complete**: pesanan dikirim menunggu konfirmasi pembeli.
4. **Refund retur (setelah dana dilepas)** diambil dari saldo tersedia artis sebesar nominal refund.
5. **Rekening pembeli untuk refund manual** tidak ada di ERD → admin minta lewat chat.
6. **OTS** dicatat atas akun "Pembeli Umum (Event)" dan tidak masuk saldo platform.
7. **Penalti rating**: batal sukarela 0,20 · lewat tenggat pelunasan 0,20 · lewat tenggat kirim 0,30 (bisa diubah admin).
8. **Admin fee** 5% (min Rp1.000) dipotong dari dana artis saat dilepas, tidak dibebankan ke pembeli.
9. **Tema** hanya mengubah tampilan (warna + banner). Isi beranda, slider, dan rekomendasi tetap dari CBF.
10. **Tema otomatis** diambil dari fandom favorit; kalau ada beberapa tema, bergiliran per hari. Fandom favorit yang belum punya tema → default Miseku.
11. **Mode gelap disimpan per perangkat** (localStorage), bukan di database — supaya tidak perlu ubah ERD dan langsung berlaku tanpa muat ulang. Default terang; *Sistem* mengikuti pengaturan HP/laptop.
12. Tombol tetap terbaca: warna tombol/tautan otomatis digelapkan bila kontras dengan teks putih < 4,5 : 1.

---
## Cara kerja rekomendasi (untuk ditulis di Bab 3/4)

Metode: **Content-Based Filtering — Multi-Hot Encoding + Weighted Cosine Similarity**
(`app/Services/ContentBasedRecommender.php`).

**1. Vektor item (multi-hot).** Setiap produk = gabungan 3 blok one-hot:
karakter, fandom, dan jenis merchandise (nilai 1 pada atribut yang dimiliki).

**2. Vektor profil user** = akumulasi vektor produk/atribut dari 5 sumber:

| Sumber | Tabel | Bobot sinyal |
| --- | --- | --- |
| Fandom favorit (onboarding) | `favorite_fandom` | 1,5 (blok fandom) |
| Histori pencarian | `search_history` | 1 |
| Produk dilihat | `product_view` | 1 |
| Isi keranjang | `cart_item` | 2 |
| Pembelian | `order_item` | 3 |

**3. Bobot atribut** (Persamaan 2.1): karakter **0,5** · fandom **0,3** · jenis **0,2**.

  sim(A,B) = Σ wᵢ·Aᵢ·Bᵢ / ( √Σ wᵢ·Aᵢ² · √Σ wᵢ·Bᵢ² )

**4.** Produk diurutkan dari skor tertinggi (rating sebagai pemecah seri). Produk yang sudah
dilihat, sudah di keranjang, sudah dibeli, atau produk sendiri tidak ditampilkan.
Halaman detail memakai rumus yang sama secara item-ke-item untuk **Produk serupa**.

**Cold start:** user baru belum punya jejak, jadi onboarding menanyakan fandom favorit —
blok fandom di vektor profil langsung terisi sehingga rekomendasi muncul sejak kunjungan pertama.
Tamu (belum login) melihat produk dengan rating tertinggi.

---

## Usulan fandom / karakter / jenis merchandise

- Ketiga tabel master (`fandom`, `fandom_character`, `merchandise_type`) di ERD punya kolom
  `status` (`active` / `pending` / `rejected`) dan `proposed_by`.
- Artis mengusulkan dari form produk → baris baru `status = 'pending'`, `proposed_by = id artis`.
  Nama yang sama (tidak peka huruf besar/kecil) tidak dibuat dobel: kalau sudah ada, langsung dipilih;
  kalau pernah ditolak, usulan ditolak.
- Usulan bisa langsung dipakai, tapi `Product::scopeVisible()` hanya menampilkan produk yang
  fandom, karakter, dan jenisnya `active`. Jadi produk baru tayang setelah admin menyetujui.
- Menyetujui karakter dari fandom yang masih usulan ikut menyetujui fandomnya; menolak fandom
  ikut menolak karakter usulan di dalamnya.
- Filter katalog, onboarding, dan rekomendasi hanya memakai master yang `active`.

## Fandom favorit

- Tabel `favorite_fandom` (M:N user–fandom) diisi saat onboarding `/mulai`, tepat setelah daftar.
  Boleh pilih lebih dari satu, boleh dilewati, bisa diubah di `/akun?tab=fandom`.
- Isinya menjadi profil awal CBF (bobot 1,5 pada blok fandom) → mengatasi **cold start**.
- `favorite_fandom` ≠ `fandom_member`: favorit = preferensi untuk rekomendasi;
  member = gabung grup diskusi (opsional, modul terpisah).
- Fandom favorit juga dipakai untuk **tema otomatis** (lihat bagian berikut).
- ERD tidak punya `user.remember_token`, jadi opsi "Ingat saya" di halaman masuk ditiadakan.

## Cara kerja tema / theming per fandom (untuk ditulis di Bab 3/4)

Dasar: survei teman memilih **"Dua-duanya"** (otomatis, tapi bisa diganti/dimatikan) dan arahan dosen
(skin berdasarkan fandom, dikelola admin, mengubah warna keseluruhan + gradasi + gambar; konten tetap CBF).

**Tabel (ERD jadi 37 tabel)**
- `fandom_theming` (baru): `fandom_id` → `fandom`, `theming_name`, `primary_color` (warna utama),
  `secondary_color` (ujung gradasi), `accent_color` (sorotan, opsional), `banner_image`, `is_active`, `created_by` → `user` (admin).
  Satu fandom boleh punya beberapa tema (nama unik per fandom).
- `user.theming_mode` (`auto` / `manual` / `off`, default `auto`) dan `user.active_theming_id` → `fandom_theming` (dipakai saat `manual`).

**Penentuan tema** (`app/Services/ThemeService.php`)
1. `off` → tampilan default Miseku.
2. `manual` → tema `active_theming_id` (kalau tema itu dinonaktifkan/dihapus → kembali ke otomatis).
3. `auto` → tema aktif dari fandom favorit (`favorite_fandom`); lebih dari satu → indeks = hari-ke-N dalam tahun mod jumlah tema.
4. Tamu: pilihan disimpan di session; otomatis = default.

**Penerapan warna** (`app/Support/ThemePalette.php`): dari 1 warna hex dibuat skala 50–900
(dicampur putih/hitam) untuk `--color-brand-*`, `--color-accent-*`, `--color-pop-*`. Tailwind v4 memakai
variabel CSS ini, jadi cukup satu `<style>` di halaman untuk mengubah seluruh warna situs.
Tingkat 600/700/800 digelapkan otomatis sampai kontras dengan putih ≥ 4,5 / 5,5 / 7 : 1.

**File terkait**: `FandomTheming` (model), `ThemeService`, `ThemePalette`, `Livewire/ThemePicker` (ikon kuas di header & Akun → Tampilan),
`Livewire/Admin/Themes` (`/admin/tema`), `components/theme-style.blade.php`, band tema di `livewire/home.blade.php`.
ERD gambar: `database/erd/ERD_Miseku.png` / `.pdf` (semua tabel) dan `database/erd/ERD_Tema_Fandom.png` (khusus tema) — hijau = tambahan tema.

---

## Kalau ada masalah

| Gejala | Penyebab & solusi |
| --- | --- |
| `Class "App\Models\User" not found` / error autoload | `composer install` belum dijalankan, atau jalankan `composer dump-autoload` |
| `SQLSTATE[HY000] [1049] Unknown database` | database `marketplace_fanmerch` belum dibuat di phpMyAdmin |
| `Table 'marketplace_fanmerch.user' doesn't exist` | `ERD_FIX_SQL_final.sql` belum di-import |
| `Table ... fandom_theming doesn't exist` / `Unknown column 'theming_mode'` | database dari SQL versi sebelumnya — jalankan `PATCH_theming.sql` |
| `Table ... favorite_fandom doesn't exist` | database dari ERD_FIX_SQL.sql — jalankan `PATCH_favorite_fandom.sql` |
| Foto upload tidak tampil | pastikan folder `public/uploads/` ada dan bisa ditulis |
| Upload foto gagal "The photo failed to upload" | ukuran > 2 MB, atau `upload_max_filesize` di `php.ini` XAMPP terlalu kecil (set ≥ 8M) |
| `Table ... .sessions doesn't exist` | `.env` masih `SESSION_DRIVER=database`, ganti ke `file` lalu `php artisan config:clear` |
| Halaman tampil tanpa warna / polos | `npm run dev` belum jalan di terminal terpisah |
| `Route [home] not defined` | jalankan `php artisan optimize:clear` |
| `No application encryption key` | jalankan `php artisan key:generate` |
| Perubahan Blade tidak muncul | `php artisan view:clear` |
