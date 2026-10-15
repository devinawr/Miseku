# Panduan Integrasi: Midtrans Sandbox, RajaOngkir (Komerce), dan PWA

Tanpa key apa pun, Miseku tetap jalan di **mode simulasi lokal** (bayar = tombol simulasi, ongkir = tabel tarif lokal).
Isi key di `.env` hanya kalau mau demo pakai API asli.

---

## 1. Midtrans Sandbox (pembayaran)

### a. Ambil key
1. Daftar / masuk di https://dashboard.midtrans.com (gratis).
2. Di pojok kiri atas, ganti **Environment → Sandbox** (bukan Production).
3. Buka **Settings → Access Keys**. Salin:
   - **Server Key** → bentuknya `SB-Mid-server-xxxxxxxx`
   - **Client Key** → bentuknya `SB-Mid-client-xxxxxxxx`

### b. Isi `.env`
```
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxx
MIDTRANS_IS_PRODUCTION=false
```
Lalu jalankan `php artisan config:clear`.

### c. Notifikasi pembayaran (webhook) — supaya status lunas otomatis
Midtrans harus bisa memanggil server kamu, jadi localhost perlu dibuka ke internet:
```
ngrok http 8000
# atau
cloudflared tunnel --url http://localhost:8000
```
Salin URL HTTPS-nya (mis. `https://abcd.ngrok-free.app`), lalu di dashboard Midtrans:
**Settings → Configuration → Payment Notification URL** = `https://abcd.ngrok-free.app/midtrans/notification`

Route `/midtrans/notification` sudah ada di `routes/web.php` dan sudah dikecualikan dari CSRF (`bootstrap/app.php`).
Kalau webhook belum dipasang, status tetap bisa dicek lewat tombol **Saya sudah bayar — cek status** di halaman bayar.

### d. Cara uji bayar
- Checkout → halaman bayar → popup Snap muncul.
- Kartu kredit uji: `4811 1111 1111 1114`, CVV `123`, exp bulan/tahun apa saja di masa depan, OTP `112233`.
- VA / QRIS / e-wallet: salin nomor VA / QR, lalu bayar lewat **https://simulator.sandbox.midtrans.com**.
- Setelah sukses, pesanan berubah ke "Dibayar" (dana masuk status ditahan sampai pesanan selesai).

---

## 2. RajaOngkir (Komerce) — ongkir asli

### a. Ambil key
1. Daftar di https://collaborator.komerce.id → menu **API Key** → buat key untuk **Shipping Cost (RajaOngkir)**.
2. Isi `.env`:
```
RAJAONGKIR_API_KEY=key_kamu
# opsional, default sudah benar:
RAJAONGKIR_BASE_URL=https://rajaongkir.komerce.id/api/v1
```
3. `php artisan config:clear`.

### b. Endpoint yang dipakai (`app/Services/ShippingService.php`)
- `GET /destination/domestic-destination?search=...` → cari kota/kecamatan saat isi alamat.
- `POST /calculate/domestic-cost` → hitung ongkir (origin, destination, weight, courier).
- Hasil ongkir di-cache 6 jam supaya kuota gratis tidak cepat habis.

### c. PENTING setelah pindah ke API asli
- **ID kota lokal (simulasi) ≠ ID Komerce.** Setelah key diisi, **alamat pembeli dan alamat toko artis harus dipilih ulang** (cari kota lagi) supaya ID-nya ID Komerce.
- Set `MISEKU_DEFAULT_ORIGIN=<ID Komerce kota asal default>` (dipakai kalau artis belum isi alamat toko).
- Kuota gratis terbatas per hari — kalau API gagal/habis, sistem menampilkan pesan, tidak membuat halaman error.
- Hapus key → otomatis kembali ke mode simulasi.

Urutan asal pengiriman: **Alamat toko** (Akun → Toko) → alamat utama artis → `MISEKU_DEFAULT_ORIGIN`.

---

## 3. PWA (bisa dipasang seperti aplikasi)

### a. File yang terlibat
| File | Fungsi |
|---|---|
| `public/manifest.json` | nama, warna, `start_url`, ikon biasa + **maskable**, shortcut (Beranda, Keranjang, Pesanan, Chat) |
| `public/sw.js` (versi cache `v3`) | service worker: cache aset & halaman offline, hanya menyimpan respons yang sukses |
| `resources/views/partials/pwa.blade.php` | daftar service worker + tangkap event `beforeinstallprompt` |
| `resources/views/components/install-app.blade.php` | tombol **Pasang aplikasi** (menu akun, menu mobile, banner beranda HP); di iPhone tampil petunjuk "Tambah ke Layar Utama" |
| `public/img/icons/icon-maskable-192.png`, `-512.png` | ikon maskable (tidak terpotong di Android) |

### b. Cara kerja singkat
1. Browser membaca `manifest.json` → tahu web ini bisa dipasang.
2. `sw.js` terdaftar → aset (CSS/JS/gambar) di-cache, kalau offline tampil halaman offline.
3. Chrome mengirim event `beforeinstallprompt` → Miseku menyimpannya dan menampilkan tombol **Pasang aplikasi**.

### c. Syarat
- Harus **HTTPS**. `http://localhost` / `127.0.0.1` dianggap aman, jadi di laptop langsung bisa.
- Untuk HP: pakai `ngrok http 8000` atau `cloudflared tunnel --url http://localhost:8000`, buka URL HTTPS-nya di HP.
  Set `APP_URL=https://alamat-tunnel` di `.env` lalu `php artisan config:clear` (kalau aset/CSS tidak termuat, set juga `ASSET_URL` sama).
- Wajib `npm run build` (bukan hanya `npm run dev`) saat demo.

### d. Cara uji & pasang
- Chrome laptop: DevTools → **Application → Manifest** (cek tidak ada error) dan **Service Workers** (status *activated*). Ikon pasang muncul di address bar / menu akun → **Pasang aplikasi**.
- Android (Chrome): menu ⋮ → **Instal aplikasi**, atau tombol di banner beranda.
- iPhone (Safari): tombol **Bagikan → Tambah ke Layar Utama**.
- Ganti ikon/aset tapi tidak berubah? Naikkan versi `CACHE` di `public/sw.js` (mis. `miseku-v4`), lalu reload 2×.
