# Miseku — Marketplace Fan Merchandise

Project **Laravel 13 utuh** untuk TA *"Pembuatan Sistem Informasi Marketplace Fan
Merchandise bagi Artis Independen di Indonesia"*.

Bedanya dengan paket sebelumnya: ini **bukan** kumpulan file yang harus di-copy ke
project lain. Ini project jadi — tinggal `composer install`, `npm install`, import
database, jalan.

Yang tidak ikut di dalam zip cuma `vendor/` dan `node_modules/`, karena dua folder itu
harus di-install di komputermu sendiri supaya cocok dengan versi PHP/Node-mu.

**Modul yang sudah jadi:** Beranda (Rekomendasi) · Katalog · Detail Produk ·
Toko Artist · Login/Register/Akun.

---

## Teknologi

| Lapisan | Teknologi |
| --- | --- |
| Bahasa | PHP 8.3+ (kamu pakai 8.5) |
| Framework | Laravel 13 |
| Komponen UI dinamis | Livewire 3 (Alpine.js sudah termasuk, tidak perlu install terpisah) |
| Template | Blade + HTML |
| Styling | Tailwind CSS 4 |
| Basis data | MySQL (XAMPP) |
| PWA | JSON manifest + Service Worker |

---

## Struktur yang penting

```
app/Livewire/            7 komponen = 7 halaman, tanpa controller
  Home.php                 beranda rekomendasi (bobot karakter/fandom/jenis)
  ProductCatalog.php       katalog: search, filter fandom→karakter→jenis, sort
  ProductDetail.php        detail produk + galeri + ulasan + catat product_view
  ArtistShop.php           toko artis (tab Karya / Review / Tentang)
  Auth/Login.php           masuk + rate limit + cek is_active
  Auth/Register.php        daftar sebagai pembeli atau artis
  Account.php              akun + riwayat produk yang dilihat
app/Models/              13 model Eloquent, nama tabel singular sesuai ERD
database/seeders/        MisekuDemoSeeder — data contoh
database/migrations/     KOSONG (disengaja — tabel dibuat dari ERD_FIX_SQL.sql)
database/migrations-opsional/
                         4 migration cadangan, kalau suatu saat mau lepas dari SQL
resources/css/app.css    palet warna Miseku (@theme Tailwind 4)
resources/views/         2 layout, 7 komponen Blade, 7 view halaman
routes/web.php           8 rute
public/                  manifest.json, sw.js, offline.html, logo, ikon, gambar dummy
```

---

## Pemasangan dari nol

### 1. Taruh folder project

Extract zip ini, lalu pindahkan foldernya ke `C:\xampp\htdocs\miseku`.
Kalau folder `miseku` yang lama masih ada, **tutup dulu VS Code**, baru hapus/rename —
kalau tidak, Windows akan bilang foldernya terkunci.

Buka folder ini di VS Code, lalu buka terminal (PowerShell) di dalamnya.

### 2. Install dependency PHP

```powershell
composer install
```

Ini yang membuat folder `vendor/`. Butuh internet, sekitar 1–3 menit.

### 3. Siapkan file .env

File `.env` sudah ikut di zip dan sudah diatur untuk XAMPP
(`DB_DATABASE=marketplace_fanmerch`, `SESSION_DRIVER=file`, `CACHE_STORE=file`,
`QUEUE_CONNECTION=sync`, `APP_LOCALE=id`). Yang perlu kamu lakukan cuma:

```powershell
php artisan key:generate
```

Kalau `.env` tidak terbawa (beberapa tool zip menyembunyikan file berawalan titik),
jalankan dulu `copy .env.example .env`.

### 4. Buat dan isi database

Nyalakan **Apache + MySQL** di XAMPP, buka `http://localhost/phpmyadmin`, lalu:

1. Buat database baru: **`marketplace_fanmerch`** (collation `utf8mb4_unicode_ci`).
2. Tab **Import** → pilih file `ERD_FIX_SQL.sql` milikmu → **Go**.
   Harusnya terbentuk 35 tabel.

Lalu isi data contoh:

```powershell
php artisan db:seed
```

> **Jangan jalankan `php artisan migrate`.** Folder `database/migrations/` sengaja
> dikosongkan karena struktur tabel sudah datang dari file SQL. Kalau di-migrate,
> Laravel malah bikin tabel `users` (jamak) yang tidak ada di ERD-mu.

### 5. Install dependency frontend

```powershell
npm install
```

### 6. Jalankan

Butuh **dua terminal**.

Terminal 1 — compiler CSS/JS, biarkan terus jalan:

```powershell
npm run dev
```

Terminal 2 — web server:

```powershell
php artisan serve
```

Buka `http://localhost:8000`.

---

## Kalau mau mengulang data dari nol

```
phpMyAdmin → DROP DATABASE marketplace_fanmerch
           → buat lagi marketplace_fanmerch
           → Import ERD_FIX_SQL.sql
```

```powershell
php artisan db:seed
```

---

## Halaman yang bisa dibuka

| URL | Modul |
| --- | --- |
| `/` | Beranda — rekomendasi, pre-order dibuka, baru dirilis |
| `/katalog` | Katalog — search, filter fandom → karakter → jenis, sort, pagination |
| `/katalog?q=keychain&status=pre_order&urut=termurah` | Contoh state katalog yang tersimpan di query string |
| `/produk/1` | Detail produk — galeri, informasi produk lengkap, info artis, ulasan |
| `/toko/1` | Toko artist — banner, tab Karya / Review / Tentang |
| `/masuk` | Login |
| `/daftar` | Register (pembeli atau artis) |
| `/akun` | Akun saya (butuh login) |

### Akun contoh

Semua password: **`password`**

| Email | Peran |
| --- | --- |
| `admin@miseku.test` | Admin |
| `kirara@miseku.test` | Artis (Kirara Studio) |
| `nakamura@miseku.test` | Artis (Atelier Nakamura) |
| `hanabi@miseku.test` | Artis (Hanabi Works) |
| `buyer1@miseku.test` … `buyer8@miseku.test` | Pembeli |

Di halaman `/masuk` ada kotak "Akun contoh" — klik salah satu email untuk mengisi
kolom otomatis, jadi tidak perlu hafal saat demo di depan dosen.

### Isi data contoh

3 toko artis · 6 fandom aktif + 1 menunggu persetujuan · 18 karakter ·
12 jenis merchandise aktif + 1 menunggu persetujuan · 24 produk (masing-masing 3 foto) ·
6 batch pre-order · ~38 pesanan selesai · ~76 ulasan ·
jejak `product_view` untuk `buyer1`–`buyer3`.

---

## Alur demo yang disarankan saat bimbingan

1. Buka `/` — beranda rekomendasi. Login sebagai `buyer1@miseku.test`, lalu bandingkan
   dengan `buyer2@miseku.test`: urutan **Rekomendasi untukmu** berbeda karena jejak
   `product_view` mereka berbeda. Ini bukti BPMN rekomendasi jalan.
2. Ketik kata kunci di kotak cari beranda → berpindah ke `/katalog?q=…`
   (pencarian memang sengaja tidak menyaring di beranda).
3. Di katalog: pilih fandom → dropdown **karakter** ikut menyesuaikan; tambah filter
   jenis + pil **Pre-order**. Tunjukkan URL-nya ikut berubah (bisa di-bookmark).
4. Klik satu kartu → detail produk. Blok **Informasi produk** memuat semua field yang
   diisi artis saat upload: nama, ukuran, harga, fandom, karakter, jenis merchandise,
   batas PO, estimasi barang jadi. Klik-klik thumbnail galeri (ini Alpine, instan).
5. Klik **Masukkan keranjang** tanpa login → dilempar ke `/masuk`.
6. Login → otomatis **kembali ke produk yang tadi**.
7. Matikan Wi-Fi lalu refresh → muncul halaman offline (bukti Service Worker jalan).

---

## Cara kerja rekomendasi (untuk ditulis di Bab 3/4)

Tiap kali user yang sudah login membuka halaman detail produk, sistem mencatat satu
baris di tabel `product_view` (dibatasi 1 catatan per produk per jam supaya tidak
membanjiri tabel). Di beranda, 30 catatan terakhir dipakai sebagai profil minat user:

| Kecocokan | Bobot |
| --- | --- |
| Karakter sama | 0,5 |
| Fandom sama | 0,3 |
| Jenis merchandise sama | 0,2 |

Skor tiap produk kandidat = jumlah bobot yang cocok, dengan rating produk dipakai
sebagai pemecah seri (`rating / 100`). Produk yang sudah pernah dilihat dikeluarkan
dari kandidat. Kalau user belum login atau belum punya jejak, beranda otomatis
menampilkan daftar dengan rating tertinggi.

Kodenya ada di `app/Livewire/Home.php`, method `rekomendasi()`.

---

## Kalau ada masalah

| Gejala | Penyebab & solusi |
| --- | --- |
| `Class "App\Models\User" not found` / error autoload | `composer install` belum dijalankan, atau jalankan `composer dump-autoload` |
| `SQLSTATE[HY000] [1049] Unknown database` | database `marketplace_fanmerch` belum dibuat di phpMyAdmin |
| `Table 'marketplace_fanmerch.user' doesn't exist` | `ERD_FIX_SQL.sql` belum di-import |
| `Table ... .sessions doesn't exist` | `.env` masih `SESSION_DRIVER=database`, ganti ke `file` lalu `php artisan config:clear` |
| Halaman tampil tanpa warna / polos | `npm run dev` belum jalan di terminal terpisah |
| `Route [home] not defined` | jalankan `php artisan optimize:clear` |
| `No application encryption key` | jalankan `php artisan key:generate` |
| Perubahan Blade tidak muncul | `php artisan view:clear` |

---

## Modul yang belum dikerjakan

Keranjang · Checkout & pembayaran · Manajemen produk sisi artis · Custom order ·
Event/booth · Panel admin (persetujuan fandom & jenis merchandise) · Chat · Settlement.

Di navbar, menu **Event** dan **Custom** sengaja dibuat abu-abu dan tidak bisa diklik,
supaya jelas bahwa modulnya memang belum masuk ruang lingkup tahap ini.
