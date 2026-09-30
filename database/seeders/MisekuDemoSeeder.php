<?php

namespace Database\Seeders;

use App\Models\ArtistProfile;
use App\Models\CustomerOrder;
use App\Models\Fandom;
use App\Models\FandomCharacter;
use App\Models\FileUpload;
use App\Models\MerchandiseType;
use App\Models\OrderItem;
use App\Models\PoBatch;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductView;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh Miseku untuk tahap bimbingan UI.
 *
 * Mengisi master atribut, 3 toko artis, 24 karya beserta galeri gambar dummy,
 * batch pre-order, lalu pesanan selesai + ulasan agar rating pada kartu produk
 * dan halaman toko benar-benar berasal dari database (bukan angka hardcode).
 */
class MisekuDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            [$admin, $artists, $buyers] = $this->seedUsers();
            $characters = $this->seedMasters($admin);
            $types      = MerchandiseType::pluck('id', 'type_name');
            $products   = $this->seedProducts($artists, $characters, $types);
            $this->seedOrdersAndReviews($products, $buyers);
            $this->seedProductViews($products, $buyers);
            $this->recalculateRatingCaches();
        });
    }

    /** ----------------------------------------------------------- users */
    private function seedUsers(): array
    {
        $admin = User::create([
            'name'     => 'Admin Miseku',
            'email'    => 'admin@miseku.test',
            'password' => 'password',
            'role'     => 'admin',
        ]);

        $artistSeed = [
            ['Kirara Studio', 'kirara', 'Studio kecil dari Bandung yang fokus ke acrylic charm dan standee. Semua ilustrasi dikerjakan sendiri, produksi dalam batch kecil supaya kualitas cetaknya terjaga.', 1],
            ['Atelier Nakamura', 'nakamura', 'Ilustrator lepas asal Surabaya. Spesialisasi poster art print dan photocard dengan gaya warna lembut. Terbuka untuk request custom lewat fitur custom merchandise.', 2],
            ['Hanabi Works', 'hanabi', 'Duo kreator dari Jakarta yang rutin ikut event Comifuro. Menyediakan ready stock untuk merchandise kecil dan pre-order untuk plushie.', 3],
        ];

        $artists = collect($artistSeed)->map(function (array $row, int $i) {
            [$shop, $slug, $bio, $img] = $row;

            $user = User::create([
                'name'          => $shop,
                'email'         => "{$slug}@miseku.test",
                'password'      => 'password',
                'role'          => 'artist',
                'phone'         => '08' . str_pad((string) (1234567800 + $i), 10, '0'),
                'profile_photo' => "/img/dummy/avatar-{$img}.jpg",
                'created_at'    => now()->subMonths(14 - $i * 3),
            ]);

            $profile = ArtistProfile::create([
                'user_id'             => $user->id,
                'shop_name'           => $shop,
                'shop_description'    => $bio,
                'bank_name'           => 'BCA',
                'bank_account_number' => '12345678' . $i,
                'created_at'          => $user->created_at,
            ]);

            FileUpload::create([
                'url'          => "/img/dummy/banner-{$img}.jpg",
                'source_table' => 'artist_profile',
                'source_id'    => $profile->id,
                'uploaded_by'  => $user->id,
                'sort_order'   => 1,
            ]);

            return $profile;
        });

        $buyerSeed = [
            'Devina W.', 'Rani Puspita', 'Bagas Pratama', 'Nadia Salsabila',
            'Ilham Kurniawan', 'Cindy Halim', 'Yoga Mahendra', 'Sarah Anindya',
        ];

        $buyers = collect($buyerSeed)->map(fn (string $name, int $i) => User::create([
            'name'          => $name,
            'email'         => 'buyer' . ($i + 1) . '@miseku.test',
            'password'      => 'password',
            'role'          => 'buyer',
            'profile_photo' => '/img/dummy/avatar-' . (($i % 3) + 1) . '.jpg',
            'created_at'    => now()->subMonths(10)->addWeeks($i),
        ]));

        return [$admin, $artists, $buyers];
    }

    /** ------------------------------------------------------- master data */
    private function seedMasters(User $admin): array
    {
        $fandomSeed = [
            'Genshin Impact'      => ['Furina', 'Raiden Shogun', 'Nahida', 'Xiao'],
            'Honkai: Star Rail'   => ['Kafka', 'Firefly', 'Robin'],
            'Jujutsu Kaisen'      => ['Gojo Satoru', 'Nobara Kugisaki', 'Megumi Fushiguro'],
            'Project Sekai'       => ['Kaito', 'Miku Hatsune', 'Kanade Yoisaki'],
            'Blue Lock'           => ['Isagi Yoichi', 'Bachira Meguru'],
            'Identity V'          => ['Fiona Gilman', 'Vera Nair'],
        ];

        $characters = [];

        foreach ($fandomSeed as $fandomName => $chars) {
            $fandom = Fandom::create(['fandom_name' => $fandomName, 'status' => 'active']);

            foreach ($chars as $char) {
                $characters[] = FandomCharacter::create([
                    'fandom_id'      => $fandom->id,
                    'character_name' => $char,
                    'status'         => 'active',
                ]);
            }
        }

        // Satu contoh ajuan artis yang masih menunggu persetujuan admin —
        // sengaja pending agar terbukti tidak muncul di dropdown filter katalog.
        Fandom::create([
            'fandom_name' => 'Wind Breaker',
            'status'      => 'pending',
            'proposed_by' => User::where('role', 'artist')->value('id'),
        ]);

        foreach (['Keychain', 'Standee', 'Poster', 'Sticker Set', 'Acrylic Charm',
                  'Enamel Pin', 'Tote Bag', 'Photocard', 'Plushie', 'Art Print',
                  'Washi Tape', 'Badge Set'] as $type) {
            MerchandiseType::create(['type_name' => $type, 'status' => 'active']);
        }

        MerchandiseType::create([
            'type_name'   => 'Mousepad',
            'status'      => 'pending',
            'proposed_by' => $admin->id,
        ]);

        return $characters;
    }

    /** ---------------------------------------------------------- products */
    private function seedProducts($artists, array $characters, $types)
    {
        /**
         * [jenis, index karakter, harga, ready|po, stok, ukuran, skema DP?]
         */
        $blueprint = [
            ['Acrylic Charm', 0,  65000,  'ready_stock', 24, '6 cm',          false],
            ['Standee',       1, 120000,  'ready_stock', 12, '15 cm',         false],
            ['Keychain',      2,  45000,  'ready_stock', 40, '5 cm',          false],
            ['Sticker Set',   3,  35000,  'ready_stock', 60, 'A6 · 8 pcs',    false],
            ['Poster',        4,  75000,  'pre_order',    0, 'A3',            false],
            ['Plushie',       5, 285000,  'pre_order',    0, '20 cm',         true],
            ['Enamel Pin',    6,  88000,  'ready_stock', 18, '4 cm',          false],
            ['Photocard',     7,  25000,  'ready_stock', 80, '5,4 × 8,6 cm',  false],
            ['Art Print',     8,  95000,  'ready_stock', 15, 'A4',            false],
            ['Tote Bag',      9, 135000,  'pre_order',    0, '38 × 40 cm',    true],
            ['Washi Tape',   10,  42000,  'ready_stock', 35, '15 mm × 5 m',   false],
            ['Badge Set',    11,  58000,  'ready_stock',  0, '44 mm · 3 pcs', false],
            ['Acrylic Charm',12,  68000,  'ready_stock', 22, '6 cm',          false],
            ['Standee',      13, 132000,  'pre_order',    0, '18 cm',         false],
            ['Keychain',     14,  48000,  'ready_stock', 30, '5 cm',          false],
            ['Poster',       15,  79000,  'ready_stock',  9, 'A3',            false],
            ['Sticker Set',  16,  38000,  'ready_stock', 45, 'A6 · 10 pcs',   false],
            ['Photocard',    17,  27000,  'ready_stock', 70, '5,4 × 8,6 cm',  false],
            ['Plushie',       0, 310000,  'pre_order',    0, '25 cm',         true],
            ['Art Print',     1,  99000,  'ready_stock', 14, 'A4',            false],
            ['Enamel Pin',    2,  92000,  'ready_stock', 20, '4,5 cm',        false],
            ['Tote Bag',      3, 128000,  'ready_stock',  8, '38 × 40 cm',    false],
            ['Badge Set',      4, 62000,  'pre_order',    0, '44 mm · 4 pcs', false],
            ['Washi Tape',     5, 45000,  'ready_stock', 28, '15 mm × 5 m',   false],
        ];

        $products = collect();

        foreach ($blueprint as $i => [$typeName, $charIndex, $price, $availability, $stock, $size, $useDp]) {
            $artist    = $artists[$i % $artists->count()];
            $character = $characters[$charIndex % count($characters)];
            $createdAt = now()->subDays(180 - $i * 6);

            $product = Product::create([
                'artist_profile_id'   => $artist->id,
                'fandom_character_id' => $character->id,
                'merchandise_type_id' => $types[$typeName],
                'product_name'        => "{$typeName} {$character->character_name}",
                'description'         => $this->description($typeName, $character->character_name, $availability),
                'price'               => $price,
                'size'                => $size,
                'stock'               => $stock,
                'availability_type'   => $availability,
                'allow_event_pickup'  => $i % 3 === 0,
                'product_status'      => 'active',
                'created_at'          => $createdAt,
            ]);

            // Galeri: 3 gambar per produk, diambil bergilir dari gambar dummy.
            foreach (range(0, 2) as $slot) {
                FileUpload::create([
                    'url'          => '/img/dummy/merch-' . ((($i + $slot) % 12) + 1) . '.jpg',
                    'source_table' => 'product',
                    'source_id'    => $product->id,
                    'uploaded_by'  => $artist->user_id,
                    'sort_order'   => $slot + 1,
                ]);
            }

            if ($availability === 'pre_order') {
                PoBatch::create([
                    'product_id'      => $product->id,
                    'price_po'        => $price - 10000,
                    'payment_scheme'  => $useDp ? 'dp' : 'full',
                    'dp_amount'       => $useDp ? round(($price - 10000) * 0.4, -3) : null,
                    'min_order'       => 15,
                    'max_slot'        => 100,
                    'opens_at'        => now()->subDays(7),
                    'closes_at'       => now()->addDays(14 + $i),
                    'estimated_month' => now()->addMonths(3)->startOfMonth(),
                    'batch_status'    => 'open',
                    'created_at'      => $createdAt,
                ]);
            }

            $products->push($product);
        }

        return $products;
    }

    private function description(string $type, string $character, string $availability): string
    {
        $intro = "{$type} ilustrasi original {$character}, digambar ulang khusus untuk rilisan ini.";

        $detail = match ($type) {
            'Acrylic Charm', 'Keychain' => 'Bahan akrilik 3 mm dengan cetak dua sisi (double print) dan finishing epoxy, dilengkapi rantai stainless.',
            'Standee'                   => 'Akrilik 4 mm dengan dudukan terpisah, cetak UV tajam sampai bagian detail rambut.',
            'Poster', 'Art Print'       => 'Dicetak di art paper 260 gsm dengan laminasi doff, dikirim dalam tabung agar tidak terlipat.',
            'Sticker Set'               => 'Bahan vinyl waterproof, potongan die-cut, aman ditempel di laptop dan tumbler.',
            'Enamel Pin'                => 'Hard enamel dengan plating gold, dua kupu pengait di belakang supaya tidak miring.',
            'Photocard'                 => 'Kertas laminasi glossy, sudah termasuk sleeve pelindung.',
            'Plushie'                   => 'Bahan minky lembut dengan bordir wajah, tinggi sesuai ukuran yang tertera.',
            'Tote Bag'                  => 'Kanvas 12 oz dengan sablon plastisol, tali jahit ganda agar kuat dipakai harian.',
            'Washi Tape'                => 'Pola berulang (seamless) dengan tinta yang tidak tembus saat ditempel di jurnal.',
            default                     => 'Dikemas rapi dengan bubble wrap dan kardus tambahan sebelum dikirim.',
        };

        $note = $availability === 'pre_order'
            ? 'Rilisan ini dibuka sebagai pre-order: produksi baru dimulai setelah periode PO ditutup dan jumlah minimum terpenuhi.'
            : 'Stok siap kirim — pesanan diproses maksimal 2 hari kerja setelah pembayaran terkonfirmasi.';

        return "{$intro}\n\n{$detail}\n\n{$note}";
    }

    /** ------------------------------------------------ orders & reviews */
    private function seedOrdersAndReviews($products, $buyers): void
    {
        $comments = [
            'Detail cetakannya rapi banget, warnanya sama seperti di foto. Packing aman sampai rumah.',
            'Pengiriman cepat dan artisnya responsif waktu ditanya soal ukuran. Recommended!',
            'Kualitas bagus untuk harganya. Bintang 4 karena sampainya sedikit lebih lama dari estimasi.',
            'Suka banget sama finishing-nya, ada bonus sticker kecil juga. Bakal repeat order.',
            'Sesuai ekspektasi. Bubble wrap-nya tebal jadi nggak ada yang lecet.',
            'Bagus, tapi warnanya sedikit lebih terang dari foto katalog.',
            'Artisnya ramah dan updatenya jelas selama proses produksi PO.',
            'Barang oke, cuma kemasan luarnya agak penyok waktu sampai.',
        ];

        $ratings = [5, 5, 4, 5, 4, 5, 5, 3, 4, 5, 5, 4];
        $counter  = 0;

        foreach ($products as $index => $product) {
            // Tidak semua produk punya ulasan — biar tampilan "belum ada ulasan" ikut teruji.
            if ($index % 5 === 4) {
                continue;
            }

            $reviewTotal = 1 + ($index % 3);

            for ($n = 0; $n < $reviewTotal; $n++) {
                $buyer     = $buyers[$counter % $buyers->count()];
                $rating    = $ratings[$counter % count($ratings)];
                $createdAt = now()->subDays(60 - ($counter % 55));
                $unitPrice = (float) $product->display_price;

                $order = CustomerOrder::create([
                    'order_code'        => 'ORD-' . $createdAt->format('Ymd') . '-' . str_pad((string) (++$counter), 4, '0', STR_PAD_LEFT),
                    'buyer_id'          => $buyer->id,
                    'artist_profile_id' => $product->artist_profile_id,
                    'order_type'        => $product->is_pre_order ? 'pre_order' : 'ready_stock',
                    'shipping_method'   => 'courier',
                    'payment_scheme'    => 'full',
                    'product_subtotal'  => $unitPrice,
                    'shipping_cost'     => 18000,
                    'admin_fee'         => round($unitPrice * 0.05),
                    'total_amount'      => $unitPrice + 18000,
                    'order_status'      => 'completed',
                    'completed_at'      => $createdAt,
                    'created_at'        => $createdAt->copy()->subDays(9),
                ]);

                OrderItem::create([
                    'customer_order_id' => $order->id,
                    'product_id'        => $product->id,
                    'quantity'          => 1,
                    'unit_price'        => $unitPrice,
                    'subtotal'          => $unitPrice,
                ]);

                // Ulasan produk (ala Shopee)
                ProductReview::create([
                    'customer_order_id' => $order->id,
                    'product_id'        => $product->id,
                    'reviewer_id'       => $buyer->id,
                    'rating'            => $rating,
                    'comment'           => $comments[$counter % count($comments)],
                    'created_at'        => $createdAt,
                ]);

                // Ulasan antar-pengguna: pembeli menilai artis
                Review::create([
                    'customer_order_id' => $order->id,
                    'reviewer_id'       => $buyer->id,
                    'reviewee_id'       => $product->artistProfile->user_id,
                    'direction'         => 'buyer_to_artist',
                    'rating'            => $rating,
                    'comment'           => $comments[($counter + 3) % count($comments)],
                    'created_at'        => $createdAt,
                ]);
            }
        }
    }

    /**
     * Jejak "produk yang pernah dilihat" untuk beberapa pembeli.
     * Ini bahan vektor rekomendasi di halaman Beranda: tiga pembeli pertama
     * dibuat punya selera fandom yang berbeda supaya hasilnya kelihatan beda
     * saat diuji login bergantian.
     */
    private function seedProductViews($products, $buyers): void
    {
        $list = $products->values();
        if ($list->isEmpty()) {
            return;
        }

        // Kelompokkan produk per fandom, lalu beri tiap pembeli satu fandom favorit.
        $perFandom = $list->groupBy(fn ($p) => $p->character->fandom_id)->values();

        foreach ($buyers->values()->take(3) as $i => $buyer) {
            $favorit = $perFandom[$i % $perFandom->count()];
            $lain    = $list->shuffle()->take(2);

            $dilihat = $favorit->take(4)->concat($lain);
            $jam     = 1;

            foreach ($dilihat as $product) {
                ProductView::create([
                    'user_id'    => $buyer->id,
                    'product_id' => $product->id,
                    'viewed_at'  => now()->subHours($jam++),
                ]);
            }
        }
    }

    /**
     * Isi ulang kolom cache rating dari tabel ulasan.
     * Di aplikasi nyata ini dijalankan sistem tiap ulasan masuk;
     * di seeder dihitung sekali di akhir.
     */
    private function recalculateRatingCaches(): void
    {
        DB::statement('
            UPDATE product p
            LEFT JOIN (
                SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS total
                FROM product_review GROUP BY product_id
            ) r ON r.product_id = p.id
            SET p.rating = COALESCE(ROUND(r.avg_rating, 2), 0),
                p.rating_count = COALESCE(r.total, 0)
        ');

        DB::statement('
            UPDATE artist_profile a
            LEFT JOIN (
                SELECT reviewee_id, AVG(rating) AS avg_rating, COUNT(*) AS total
                FROM review WHERE direction = ? GROUP BY reviewee_id
            ) r ON r.reviewee_id = a.user_id
            SET a.rating = COALESCE(ROUND(r.avg_rating, 2), 0),
                a.rating_count = COALESCE(r.total, 0)
        ', ['buyer_to_artist']);
    }
}
