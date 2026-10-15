<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\ArtistProfile;
use App\Models\ChatMessage;
use App\Models\CustomOffer;
use App\Models\CustomRequest;
use App\Models\CustomerOrder;
use App\Models\DesignOption;
use App\Models\DesignPoll;
use App\Models\DiscussionTopic;
use App\Models\Event;
use App\Models\EventBooth;
use App\Models\Fandom;
use App\Models\FandomCharacter;
use App\Models\FandomMember;
use App\Models\FandomTheming;
use App\Models\FileUpload;
use App\Models\MerchandiseType;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\PoBatch;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductView;
use App\Models\Review;
use App\Models\SearchHistory;
use App\Models\TopicMessage;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\CancellationService;
use App\Services\OrderService;
use App\Services\OtsService;
use App\Services\PaymentService;
use App\Services\PollService;
use App\Services\PoService;
use App\Services\RatingService;
use App\Services\ReturnService;
use App\Services\WithdrawalService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data contoh Miseku.
 *
 * Mengisi master atribut, 3 toko artis, 24 produk beserta galeri gambar dummy,
 * batch pre-order, pesanan selesai + ulasan (lengkap dengan pembayaran & mutasi saldo),
 * lalu skenario demo yang dijalankan LEWAT SERVICE ASLI aplikasi (checkout, bayar simulasi,
 * produksi PO, pelunasan, kirim, pickup event, batal, retur, custom, voting, tarik dana, OTS)
 * supaya setiap status bisa langsung dicek di UI.
 */
class MisekuDemoSeeder extends Seeder
{
    /** user_id => address_id utama */
    private array $addresses = [];

    public function run(): void
    {
        // email notifikasi tidak perlu benar-benar dikirim saat seeding
        config(['mail.default' => 'array']);

        try {
            DB::transaction(function () {
                [$admin, $artists, $buyers] = $this->seedUsers();
                $this->seedSettingsAndAddresses($admin, $artists, $buyers);
                $characters = $this->seedMasters($admin);
                $types      = MerchandiseType::pluck('id', 'type_name');
                $products   = $this->seedProducts($artists, $characters, $types);
                $this->seedOrdersAndReviews($products, $buyers);
                $this->seedProductViews($products, $buyers);
                $this->seedFavoritesAndSearches($buyers);
                $this->seedThemes($admin, $buyers);
                $booths = $this->seedEvents($admin, $artists);
                $this->seedScenarios($admin, $artists, $buyers, $products, $booths);
                $this->seedPolls($artists, $buyers, $characters, $types);
                $this->seedCustom($artists, $buyers, $types);
                $this->seedCommunity($admin, $artists, $buyers, $products);
                $this->seedChats($artists, $buyers, $products);
                $this->recalculateRatingCaches();
                $this->seedWithdrawals($admin, $artists);
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    /** Jalankan $fn seolah-olah terjadi pada waktu $at (service memakai now()). */
    private function at(Carbon $at, callable $fn)
    {
        Carbon::setTestNow($at);
        try {
            return $fn();
        } finally {
            Carbon::setTestNow();
        }
    }

    /** ------------------------------------------- pengaturan & alamat */
    private function seedSettingsAndAddresses(User $admin, $artists, $buyers): void
    {
        PlatformSetting::create([
            'fee_percentage' => 5, 'fee_minimum' => 1000, 'penalty_voluntary' => 0.20,
            'penalty_settlement_timeout' => 0.20, 'penalty_shipping_timeout' => 0.30,
            'effective_from' => now()->subYears(2), 'changed_by_id' => $admin->id,
        ]);

        $make = function (int $userId, string $label, string $name, string $street, string $cityId, string $postal, bool $primary) {
            $a = Address::create([
                'user_id' => $userId, 'address_label' => $label, 'recipient_name' => $name, 'full_address' => $street,
                'rajaongkir_city_id' => $cityId, 'province' => \App\Support\Cities::LIST[$cityId][1] ?? null,
                'postal_code' => $postal, 'is_primary' => $primary,
            ]);
            if ($primary) {
                $this->addresses[$userId] = $a->id;
            }
        };

        // alamat studio artis = kota asal pengiriman (ongkir)
        $artistAddr = [
            ['Jl. Dago No. 112, Coblong', '23', '40135'],
            ['Jl. Raya Darmo No. 45, Wonokromo', '444', '60241'],
            ['Jl. Kemang Raya No. 8, Mampang Prapatan', '153', '12730'],
        ];
        foreach ($artists->values() as $i => $profile) {
            [$street, $city, $postal] = $artistAddr[$i];
            $make($profile->user_id, 'Studio', $profile->shop_name, $street, $city, $postal, true);
            $make($profile->user_id, Address::SHOP_LABEL, $profile->shop_name, $street, $city, $postal, false);
        }

        $buyerAddr = [
            ['Jl. Kaliurang Km 5 No. 21, Sleman', '501', '55281'],
            ['Jl. Cikini Raya No. 73, Menteng', '152', '10330'],
            ['Jl. Setiabudi No. 190, Sukasari', '23', '40154'],
            ['Jl. Mayjen Sungkono No. 9, Dukuh Pakis', '444', '60225'],
            ['Jl. Gatot Subroto No. 17, Kota Baru', '156', '36129'],
            ['Jl. Tanjung Duren Raya No. 5, Grogol', '151', '11470'],
            ['Jl. Raya Abepura No. 30, Abepura', '157', '99351'],
            ['Jl. Pemuda No. 66, Rawamangun', '154', '13220'],
        ];
        foreach ($buyers->values() as $i => $buyer) {
            [$street, $city, $postal] = $buyerAddr[$i];
            $make($buyer->id, 'Rumah', $buyer->name, $street, $city, $postal, true);
        }
        // buyer1 punya 2 alamat (uji ganti alamat di checkout)
        $make($buyers->first()->id, 'Kos', $buyers->first()->name, 'Jl. Tubagus Ismail No. 14, Coblong', '23', '40134', false);
    }

    /** ------------------------------------------------------- event */
    private function seedEvents(User $admin, $artists): array
    {
        $a = $artists->values();
        $mk = fn (string $name, string $loc, $start, $end) => Event::create([
            'event_name' => $name, 'location' => $loc, 'start_date' => $start, 'end_date' => $end, 'created_by' => $admin->id,
        ]);

        $past = $mk('Comifuro 19', 'ICE BSD Hall 7–8, Tangerang', today()->subDays(60), today()->subDays(59));
        $now  = $mk('Comifuro 20', 'ICE BSD Hall 5–6, Tangerang', today()->subDay(), today()->addDay());
        $next = $mk('Indonesia Comic Con 2026', 'JCC Senayan, Jakarta', today()->addDays(21), today()->addDays(22));

        EventBooth::create(['event_id' => $past->id, 'artist_profile_id' => $a[0]->id, 'booth_number' => 'K-14', 'operating_hours' => '10:00-20:00', 'attend_days' => '1,2']);
        EventBooth::create(['event_id' => $past->id, 'artist_profile_id' => $a[2]->id, 'booth_number' => 'K-15', 'operating_hours' => '10:00-20:00', 'attend_days' => '2']);

        $booths = [];
        foreach (['A-12', 'B-03', 'C-21'] as $i => $no) {
            $booths[$i] = EventBooth::create(['event_id' => $now->id, 'artist_profile_id' => $a[$i]->id, 'booth_number' => $no, 'operating_hours' => '10:00-21:00', 'attend_days' => '1,2,3']);
        }

        EventBooth::create(['event_id' => $next->id, 'artist_profile_id' => $a[0]->id, 'booth_number' => 'D-07', 'operating_hours' => '10:00-21:00', 'attend_days' => '1,2']);
        EventBooth::create(['event_id' => $next->id, 'artist_profile_id' => $a[2]->id, 'booth_number' => 'D-08', 'operating_hours' => '12:00-21:00', 'attend_days' => '2']);

        return $booths;
    }

    /**
     * ------------------------------------------------ skenario pesanan
     * Semua lewat service asli supaya status, tagihan, saldo & notifikasi konsisten.
     */
    private function seedScenarios(User $admin, $artists, $buyers, $products, array $booths): void
    {
        $orders = app(OrderService::class);
        $pay    = app(PaymentService::class);
        $b = $buyers->values();
        $p = $products->values();
        $a = $artists->values();

        $courier = fn ($product) => [$product->artist_profile_id => ['method' => 'courier', 'courier' => 'jne', 'service' => 'REG']];
        $buy = fn (User $buyer, $product, int $qty = 1, string $scheme = 'full', ?array $ship = null) => $orders->checkout(
            $buyer, [['product_id' => $product->id, 'quantity' => $qty, 'scheme' => $scheme]], $ship ?? $courier($product), $this->addresses[$buyer->id] ?? null
        )->first();
        $payOrder = function (CustomerOrder $o) use ($pay) {
            $invoice = $o->payments()->where('payment_status', 'pending')->latest('id')->first();
            $pay->markSuccess($invoice, 'QRIS (simulasi)', 'SIM-' . strtoupper(Str::random(10)));

            return $o->fresh();
        };
        $days = fn (int $d) => now()->subDays($d);

        // 1) buyer1 — RS belum dibayar (uji halaman bayar simulasi)
        $this->at($days(0)->subHour(), fn () => $buy($b[0], $p[0], 2));

        // 2) buyer1 — RS sudah dibayar, menunggu dikirim artis (Nakamura)
        $this->at($days(1), fn () => $payOrder($buy($b[0], $p[1])));

        // 3) buyer1 — RS dikirim, tinggal konfirmasi diterima (Hanabi)
        $o3 = $this->at($days(4), fn () => $payOrder($buy($b[0], $p[2], 3)));
        $this->at($days(2), fn () => $orders->ship($o3, 'JNE REG', 'JNE0012345678'));

        // 4) buyer1 — RS selesai kemarin (bisa ulasan & retur) (Kirara)
        $o4 = $this->at($days(6), fn () => $payOrder($buy($b[0], $p[6])));
        $this->at($days(4), fn () => $orders->ship($o4, 'SiCepat REG', 'SCP7781230011'));
        $this->at($days(1), fn () => $orders->confirmReceived($o4->fresh(), $b[0]));

        // 5) buyer1 — PO skema DP dibayar DP, batch masih buka (Hanabi Plushie)
        $this->at($days(2), fn () => $payOrder($buy($b[0], $p[5], 1, 'dp')));

        // 6) buyer1 — RS ambil di booth Comifuro 20 → siap diambil (QR) (Kirara)
        $pickup = [$p[12]->artist_profile_id => ['method' => 'event_pickup', 'booth_id' => $booths[0]->id]];
        $o6 = $this->at($days(3), fn () => $payOrder($buy($b[0], $p[12], 1, 'full', $pickup)));
        $orders->readyForPickup($o6);

        // 6b) buyer3 — pickup booth Kirara juga, sudah siap (dipakai uji scan QR)
        $o6b = $this->at($days(3), fn () => $payOrder($buy($b[2], $p[0], 1, 'full', $pickup)));
        $orders->readyForPickup($o6b);

        // 7) buyer2 — RS dibayar lalu ajukan pembatalan (menunggu keputusan Kirara)
        $o7 = $this->at($days(1), fn () => $payOrder($buy($b[1], $p[3], 2)));
        app(CancellationService::class)->request($o7, $b[1], 'Salah pilih varian, mau ganti ke karakter lain.');

        // 8) buyer3 — RS selesai lalu ajukan retur (Nakamura)
        $o8 = $this->at($days(5), fn () => $payOrder($buy($b[2], $p[7], 2)));
        $this->at($days(3), fn () => $orders->ship($o8, 'JNE REG', 'JNE0098765432'));
        $this->at($days(1)->subHours(5), fn () => $orders->confirmReceived($o8->fresh(), $b[2]));
        app(ReturnService::class)->request($o8->fresh(), $b[2], 'Satu photocard sudut kanannya terlipat saat sampai.', ['/img/products/product-8.jpg']);

        // 9) PO DP sampai produksi selesai (Kirara — Tote Bag, batch target 3 pcs)
        $tote  = $p[9];
        $batch = PoBatch::where('product_id', $tote->id)->latest('id')->first();
        $batch->update(['min_order' => 3, 'opens_at' => $days(30), 'created_at' => $days(30)]);
        $this->at($days(25), fn () => $payOrder($buy($b[0], $tote, 1, 'dp')));          // buyer1: nanti menunggu pelunasan
        $po2 = $this->at($days(24), fn () => $payOrder($buy($b[1], $tote, 1, 'dp')));   // buyer2: lunas → dikirim
        $this->at($days(23), fn () => $payOrder($buy($b[3], $tote, 1, 'full')));        // buyer4: full → siap kirim
        $this->at($days(20), fn () => app(PoService::class)->closeNow($batch->fresh()));
        $this->at($days(18), fn () => app(PoService::class)->startProduction($batch->fresh()));
        $this->at($days(2), fn () => app(PoService::class)->completeProduction($batch->fresh()));
        $this->at($days(1), fn () => $payOrder($po2->fresh()));
        $orders->ship($po2->fresh(), 'JNE REG', 'JNE0055511122');
        // batch berikutnya dibuka lagi supaya produk tetap bisa di-PO
        PoBatch::create([
            'product_id' => $tote->id, 'price_po' => $batch->price_po, 'payment_scheme' => 'dp', 'dp_amount' => $batch->dp_amount,
            'min_order' => 10, 'max_slot' => 60, 'opens_at' => now()->subDay(), 'closes_at' => now()->addDays(20),
            'estimated_month' => now()->addMonths(3)->startOfMonth(), 'batch_status' => 'open',
        ]);

        // 10) PO gagal target minimum → otomatis dibatalkan & refund (Nakamura — Badge Set)
        $badge = $p[22];
        $bb = PoBatch::where('product_id', $badge->id)->latest('id')->first();
        $bb->update(['min_order' => 10, 'opens_at' => $days(20), 'created_at' => $days(20)]);
        $this->at($days(15), fn () => $payOrder($buy($b[5], $badge, 2)));
        $this->at($days(14), fn () => $payOrder($buy($b[6], $badge, 1)));
        $this->at($days(10), fn () => app(PoService::class)->closeNow($bb->fresh()));
        PoBatch::create([
            'product_id' => $badge->id, 'price_po' => $bb->price_po, 'payment_scheme' => 'full', 'dp_amount' => null,
            'min_order' => 5, 'max_slot' => 50, 'opens_at' => now()->subDays(2), 'closes_at' => now()->addDays(25),
            'estimated_month' => now()->addMonths(2)->startOfMonth(), 'batch_status' => 'open',
        ]);

        // 11) PO biasa dibayar penuh, batch masih buka (buyer4 → Nakamura Poster)
        $this->at($days(3), fn () => $payOrder($buy($b[3], $p[4], 2)));

        // 12) OTS di booth Hanabi (Comifuro 20)
        $ots = app(OtsService::class);
        $ots->buyerAccount();
        $ots->sell($a[2], $booths[2], [$p[2]->id => 2, $p[8]->id => 1], 'Tunai');
        $ots->sell($a[2], $booths[2], [$p[14]->id => 1], 'QRIS booth');
    }

    /** ------------------------------------------------ voting desain */
    private function seedPolls($artists, $buyers, array $characters, $types): void
    {
        $a = $artists->values();
        $b = $buyers->values();
        $polls = app(PollService::class);

        // Kirara: voting sedang berjalan
        $running = DesignPoll::create([
            'artist_profile_id' => $a[0]->id, 'poll_title' => 'Desain acrylic stand Furina edisi ulang tahun',
            'description' => 'Pilih pose favoritmu! Desain pemenang akan dibuka sebagai pre-order bulan depan.',
            'start_date' => now()->subDays(3), 'end_date' => now()->addDays(4), 'poll_status' => 'running',
        ]);
        $opts = collect([['/img/products/product-1.jpg', 'Pose duduk di panggung opera'], ['/img/products/product-13.jpg', 'Pose menari dengan Fontaine'], ['/img/products/product-19.jpg', 'Chibi dengan Mademoiselle Crabaletta']])
            ->map(fn ($o) => DesignOption::create(['design_poll_id' => $running->id, 'design_image' => $o[0], 'description' => $o[1]]));
        foreach ([1, 2, 3, 4, 5] as $n => $i) {
            $polls->vote($running, $opts[$n % 3 === 0 ? 0 : ($n % 2)], $b[$i]);
        }

        // Nakamura: voting selesai → desain pemenang dijadikan PO
        $done = $this->at(now()->subDays(15), fn () => DesignPoll::create([
            'artist_profile_id' => $a[1]->id, 'poll_title' => 'Art print Gojo — pilih palet warnanya',
            'description' => 'Dua palet warna untuk art print A4 berikutnya.',
            'start_date' => now(), 'end_date' => now()->addDays(7), 'poll_status' => 'running',
        ]));
        $o1 = DesignOption::create(['design_poll_id' => $done->id, 'design_image' => '/img/products/product-5.jpg', 'description' => 'Palet biru malam']);
        $o2 = DesignOption::create(['design_poll_id' => $done->id, 'design_image' => '/img/products/product-11.jpg', 'description' => 'Palet senja oranye']);
        foreach ([0, 2, 3, 6, 7] as $n => $i) {
            $this->at(now()->subDays(12), fn () => $polls->vote($done, $n < 3 ? $o1 : $o2, $b[$i]));
        }
        $winner = $this->at(now()->subDays(8), fn () => $polls->finish($done));

        $gojo = collect($characters)->first(fn ($c) => $c->character_name === 'Gojo Satoru');
        $product = Product::create([
            'artist_profile_id' => $a[1]->id, 'fandom_character_id' => $gojo->id, 'merchandise_type_id' => $types['Art Print'],
            'design_option_id' => $winner->id, 'product_name' => 'Art Print Gojo Satoru — Palet Biru Malam',
            'description' => "Desain pemenang voting \"{$done->poll_title}\".\n\nDicetak di art paper 260 gsm dengan laminasi doff.",
            'price' => 99000, 'size' => 'A4', 'stock' => 0, 'availability_type' => 'pre_order', 'allow_event_pickup' => false,
            'product_status' => 'active', 'created_at' => now()->subDays(7),
        ]);
        FileUpload::create(['url' => $winner->design_image, 'source_table' => 'product', 'source_id' => $product->id, 'uploaded_by' => $a[1]->user_id, 'sort_order' => 1]);
        PoBatch::create([
            'product_id' => $product->id, 'price_po' => 89000, 'payment_scheme' => 'full', 'min_order' => 10, 'max_slot' => 80,
            'opens_at' => now()->subDays(7), 'closes_at' => now()->addDays(10), 'estimated_month' => now()->addMonths(2)->startOfMonth(),
            'batch_status' => 'open', 'created_at' => now()->subDays(7),
        ]);
        $polls->announcePo($winner, $product);
    }

    /** ------------------------------------------------ custom merchandise */
    private function seedCustom($artists, $buyers, $types): void
    {
        $a = $artists->values();
        $b = $buyers->values();

        // buyer1: permintaan terbuka dengan 2 penawaran (pilih salah satu → checkout)
        $r1 = $this->at(now()->subDays(2), fn () => CustomRequest::create([
            'buyer_id' => $b[0]->id, 'merchandise_type_id' => $types['Standee'], 'design_character' => 'Furina & Neuvillette (couple)',
            'quantity' => 2, 'specification' => "Standee akrilik 15 cm, pose berdampingan, background panggung opera.\nWarna dominan biru-putih.",
            'budget_price' => 300000, 'request_status' => 'offered',
        ]));
        FileUpload::create(['url' => '/img/products/product-2.jpg', 'source_table' => 'custom_request', 'source_id' => $r1->id, 'uploaded_by' => $b[0]->id, 'sort_order' => 1]);
        CustomOffer::create(['custom_request_id' => $r1->id, 'artist_profile_id' => $a[0]->id, 'offer_price' => 280000, 'estimated_days' => 14, 'note' => 'Bisa revisi sketsa 2x. Sudah termasuk dudukan.', 'offer_status' => 'pending']);
        CustomOffer::create(['custom_request_id' => $r1->id, 'artist_profile_id' => $a[1]->id, 'offer_price' => 320000, 'estimated_days' => 10, 'note' => 'Akrilik 5 mm, cetak UV dua sisi.', 'offer_status' => 'pending']);

        // buyer3: permintaan baru, belum ada penawaran
        $this->at(now()->subHours(6), fn () => CustomRequest::create([
            'buyer_id' => $b[2]->id, 'merchandise_type_id' => $types['Keychain'], 'design_character' => 'Kafka versi chibi',
            'quantity' => 5, 'specification' => 'Keychain akrilik 5 cm, chibi Kafka pegang gunting. Untuk souvenir ulang tahun teman.',
            'budget_price' => 200000, 'request_status' => 'open',
        ]));

        // buyer2: penawaran Nakamura dipilih → checkout → dibayar → sedang diproduksi
        $r2 = $this->at(now()->subDays(9), fn () => CustomRequest::create([
            'buyer_id' => $b[1]->id, 'merchandise_type_id' => $types['Poster'], 'design_character' => 'Gojo Satoru — ilustrasi pesanan',
            'quantity' => 1, 'specification' => 'Poster A3 Gojo dengan latar Shibuya malam hari.', 'budget_price' => 250000, 'request_status' => 'open',
        ]));
        $offer = $this->at(now()->subDays(8), function () use ($r2, $a) {
            $r2->update(['request_status' => 'offered']);

            return CustomOffer::create(['custom_request_id' => $r2->id, 'artist_profile_id' => $a[1]->id, 'offer_price' => 225000, 'estimated_days' => 12, 'note' => 'Termasuk 1x revisi warna.', 'offer_status' => 'pending']);
        });
        $order = $this->at(now()->subDays(7), fn () => app(OrderService::class)->checkoutCustom($b[1], $offer, ['method' => 'courier', 'courier' => 'jne', 'service' => 'REG'], $this->addresses[$b[1]->id]));
        $this->at(now()->subDays(7)->addHour(), function () use ($order) {
            $inv = $order->payments()->where('payment_status', 'pending')->first();
            app(PaymentService::class)->markSuccess($inv, 'BCA Virtual Account (simulasi)', 'SIM-' . strtoupper(Str::random(10)));
        });
        $this->at(now()->subDays(6), fn () => app(OrderService::class)->setProductionStatus($order->fresh(), 'in_production'));
    }

    /** ------------------------------------------------ komunitas fandom */
    private function seedCommunity(User $admin, $artists, $buyers, $products): void
    {
        $f = Fandom::pluck('id', 'fandom_name');
        $a = $artists->values();
        $b = $buyers->values();

        $join = fn (string $fandom, int $userId, string $role = 'member', int $daysAgo = 20) => FandomMember::create([
            'fandom_id' => $f[$fandom], 'user_id' => $userId, 'role' => $role, 'joined_at' => now()->subDays($daysAgo),
        ]);

        $join('Genshin Impact', $b[0]->id, 'moderator', 40);
        foreach ([1, 2, 4] as $i) {
            $join('Genshin Impact', $b[$i]->id, 'member', 30 - $i);
        }
        $join('Genshin Impact', $a[0]->user_id, 'member', 35);
        $join('Jujutsu Kaisen', $b[1]->id, 'moderator', 33);
        $join('Jujutsu Kaisen', $b[3]->id);
        $join('Jujutsu Kaisen', $a[1]->user_id);
        $join('Honkai: Star Rail', $b[2]->id, 'member', 15);
        $join('Project Sekai', $b[0]->id, 'member', 12);

        $post = function (DiscussionTopic $t, User|int $user, string $body, int $hoursAgo, ?TopicMessage $parent = null, ?int $productId = null, ?int $artistId = null) {
            return TopicMessage::create([
                'discussion_topic_id' => $t->id, 'parent_message_id' => $parent?->id, 'user_id' => $user instanceof User ? $user->id : $user,
                'message_body' => $body, 'attached_product_id' => $productId, 'attached_artist_id' => $artistId, 'sent_at' => now()->subHours($hoursAgo),
            ]);
        };
        $topic = fn (string $fandom, User $creator, string $title, int $daysAgo) => $this->at(now()->subDays($daysAgo),
            fn () => DiscussionTopic::create(['fandom_id' => $f[$fandom], 'creator_id' => $creator->id, 'title' => $title]));

        $t1 = $topic('Genshin Impact', $b[0], 'Rekomendasi artis lokal buat merch Furina?', 5);
        $m1 = $post($t1, $b[0], 'Lagi cari acrylic charm Furina yang cetakannya rapi. Ada rekomendasi artis lokal?', 120);
        $post($t1, $b[1], 'Coba cek Kirara Studio, aku pernah beli dan packing-nya aman banget.', 110, $m1, null, $a[0]->id);
        $post($t1, $a[0]->user_id, 'Halo! Kebetulan charm Furina masih ready, bisa juga ambil di booth Comifuro 20 ya 🙌', 100, $m1, $products->values()[0]->id);
        $post($t1, $b[4], 'Mau ikut nitip info kalau ada PO plushie-nya juga dong.', 30);

        $t2 = $topic('Genshin Impact', $b[2], 'Jadwal meet-up fans di Comifuro 20', 2);
        $post($t2, $b[2], 'Ada yang mau meet-up di Comifuro hari kedua? Kumpul dekat booth A-12 jam 13.00.', 40);

        $t3 = $topic('Jujutsu Kaisen', $b[1], 'Pamer koleksi merch Gojo kalian!', 8);
        $m3 = $post($t3, $b[1], 'Barusan dapat art print Gojo, warnanya cakep. Kalian koleksi apa aja?', 190);
        $post($t3, $b[3], 'Aku ikut PO poster-nya Atelier Nakamura, nunggu produksi nih.', 170, $m3, null, $a[1]->id);
    }

    /** ------------------------------------------------ chat */
    private function seedChats($artists, $buyers, $products): void
    {
        $a = $artists->values();
        $b = $buyers->values();
        $p = $products->values();

        $rows = [
            [$b[0]->id, $a[0]->user_id, 'Halo kak, charm Furina ini double print kan?', 26, $p[0]->id],
            [$a[0]->user_id, $b[0]->id, 'Halo! Iya double print + epoxy di kedua sisi ya.', 25, null],
            [$b[0]->id, $a[0]->user_id, 'Oke kak, aku ambil di booth Comifuro aja ya nanti.', 3, null],
            [$b[1]->id, $a[1]->user_id, 'Kak, untuk poster custom-nya boleh request font judul?', 140, null],
            [$a[1]->user_id, $b[1]->id, 'Boleh banget, kirim referensinya di sini ya.', 138, null],
        ];
        foreach ($rows as [$from, $to, $body, $hours, $productId]) {
            ChatMessage::create(['sender_id' => $from, 'receiver_id' => $to, 'product_id' => $productId, 'message_body' => $body, 'sent_at' => now()->subHours($hours)]);
        }
    }

    /** ------------------------------------------------ tarik dana */
    private function seedWithdrawals(User $admin, $artists): void
    {
        $a = $artists->values();
        $svc = app(WithdrawalService::class);

        // Hanabi: penarikan selesai (ada bukti transfer)
        $w = $this->at(now()->subDays(10), fn () => $svc->request($a[2]->fresh(), 100000));
        $this->at(now()->subDays(9), fn () => $svc->markProcessing($w, $admin));
        $this->at(now()->subDays(9)->addHours(2), fn () => $svc->complete($w->fresh(), $admin, '/img/dummy/banner-3.jpg'));

        // Atelier Nakamura: penarikan ditolak (alasan terlihat di Studio → Saldo → detail)
        $w = $this->at(now()->subDays(6), fn () => $svc->request($a[1]->fresh(), 75000));
        $this->at(now()->subDays(5), fn () => $svc->reject($w->fresh(), $admin, 'Nama pemilik rekening tidak sama dengan nama toko. Perbarui rekening di Akun → Toko saya lalu ajukan lagi.'));

        // Kirara: penarikan baru diajukan (menunggu admin)
        $svc->request($a[0]->fresh(), 150000);
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
                'allow_event_pickup'  => $i % 2 === 0,
                'product_status'      => 'active',
                'created_at'          => $createdAt,
            ]);

            // Foto produk contoh: public/img/products/product-{urutan}.jpg (1 foto per produk).
            // Catatan: foto contoh hanya untuk uji tampilan lokal, bukan untuk dipublikasikan.
            FileUpload::create([
                'url'          => '/img/products/product-' . ($i + 1) . '.jpg',
                'source_table' => 'product',
                'source_id'    => $product->id,
                'uploaded_by'  => $artist->user_id,
                'sort_order'   => 1,
            ]);

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

                // alamat, resi, pembayaran lunas + dana ditahan → dilepas saat selesai
                $order->update([
                    'address_id'      => $this->addresses[$buyer->id] ?? null,
                    'courier'         => 'JNE REG',
                    'tracking_number' => 'JNE' . str_pad((string) (880000 + $counter), 10, '0', STR_PAD_LEFT),
                ]);
                $this->at($order->created_at->copy()->addHour(), function () use ($order) {
                    $pay = Payment::create([
                        'customer_order_id' => $order->id, 'payment_type' => 'full', 'amount' => $order->total_amount,
                        'payment_method' => 'QRIS (simulasi)', 'midtrans_order_id' => $order->order_code . '-FULL',
                        'midtrans_transaction_id' => 'SIM-' . strtoupper(Str::random(10)), 'payment_status' => 'success', 'paid_at' => now(),
                    ]);
                    app(BalanceService::class)->hold($order, $pay, (float) $order->total_amount);
                });
                $this->at($createdAt->copy(), fn () => app(BalanceService::class)->release($order));

                // sebagian pesanan juga diulas balik oleh artis (rating pembeli)
                if ($counter % 3 === 0) {
                    Review::create([
                        'customer_order_id' => $order->id,
                        'reviewer_id'       => $product->artistProfile->user_id,
                        'reviewee_id'       => $buyer->id,
                        'direction'         => 'artist_to_buyer',
                        'rating'            => $counter % 9 === 0 ? 4 : 5,
                        'comment'           => 'Pembeli ramah dan cepat konfirmasi. Terima kasih!',
                        'created_at'        => $createdAt->copy()->addDay(),
                    ]);
                }

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
     * Fandom favorit (jawaban onboarding) + contoh histori pencarian.
     * buyer1–buyer3 sudah punya profil minat; buyer4 dst. sengaja kosong
     * agar alur onboarding / cold start bisa didemokan.
     */
    private function seedFavoritesAndSearches($buyers): void
    {
        $fandoms = Fandom::pluck('id', 'fandom_name');
        $chars   = FandomCharacter::pluck('id', 'character_name');
        $types   = MerchandiseType::pluck('id', 'type_name');

        $plan = [
            0 => [['Genshin Impact', 'Project Sekai'], [['furina', 'Furina', null], ['acrylic charm', null, 'Acrylic Charm']]],
            1 => [['Jujutsu Kaisen'], [['gojo', 'Gojo Satoru', null]]],
            2 => [['Honkai: Star Rail', 'Genshin Impact'], [['kafka', 'Kafka', null], ['photocard', null, 'Photocard']]],
        ];

        foreach ($plan as $i => [$favs, $searches]) {
            $buyer = $buyers->values()[$i] ?? null;
            if (! $buyer) {
                continue;
            }

            $buyer->favoriteFandoms()->attach(
                collect($favs)->map(fn ($f) => $fandoms[$f] ?? null)->filter()->all(),
                ['created_at' => now()->subDays(20)]
            );

            foreach ($searches as $n => [$keyword, $char, $type]) {
                $charId = $char ? ($chars[$char] ?? null) : null;
                SearchHistory::create([
                    'user_id'             => $buyer->id,
                    'keyword'             => $keyword,
                    'fandom_character_id' => $charId,
                    'fandom_id'           => $charId ? FandomCharacter::find($charId)?->fandom_id : null,
                    'merchandise_type_id' => $type ? ($types[$type] ?? null) : null,
                    'searched_at'         => now()->subDays($n + 1),
                ]);
            }
        }
    }

    /**
     * ------------------------------------------- tema / skin fandom
     * Dikelola admin. Genshin Impact punya 2 tema → pada mode otomatis bergiliran per hari.
     *  buyer1 = otomatis (fandom favorit Genshin + Project Sekai)
     *  buyer2 = pilih sendiri (tema Project Sekai, padahal favoritnya Jujutsu Kaisen)
     *  buyer3 = tema dimatikan (tampilan default Miseku)
     */
    private function seedThemes(User $admin, $buyers): void
    {
        $f = Fandom::pluck('id', 'fandom_name');
        $plan = [
            ['Genshin Impact', 'Teyvat Starlight', '#2f5d9e', '#d4a64a', '#5fc4c9', '/img/dummy/banner-1.jpg'],
            ['Genshin Impact', 'Fontaine Hydro', '#1d6fb8', '#5cc8d8', '#f2c14e', '/img/dummy/banner-2.jpg'],
            ['Honkai: Star Rail', 'Astral Express', '#4b3a8f', '#e2a75c', '#8bd3f7', '/img/dummy/banner-3.jpg'],
            ['Jujutsu Kaisen', 'Cursed Energy', '#2a3a8f', '#b91c5c', '#38bdf8', '/img/dummy/banner-2.jpg'],
            ['Project Sekai', 'Miku Teal', '#0f9e99', '#e85fa5', '#ffd166', '/img/dummy/banner-1.jpg'],
            ['Blue Lock', 'Egoist Blue', '#1d4ed8', '#22d3ee', '#a3e635', '/img/dummy/banner-3.jpg'],
            ['Identity V', 'Manor Night', '#5b3a29', '#9b2c2c', '#d6b370', '/img/dummy/banner-2.jpg'],
        ];

        $themes = [];
        foreach ($plan as [$fandom, $name, $primary, $secondary, $accent, $banner]) {
            if (! isset($f[$fandom])) {
                continue;
            }
            $themes[$name] = FandomTheming::create([
                'fandom_id' => $f[$fandom], 'theming_name' => $name, 'primary_color' => $primary,
                'secondary_color' => $secondary, 'accent_color' => $accent, 'banner_image' => $banner,
                'is_active' => true, 'created_by' => $admin->id,
            ]);
        }

        $b = $buyers->values();
        if (isset($b[1], $themes['Miku Teal'])) {
            $b[1]->forceFill(['theming_mode' => 'manual', 'active_theming_id' => $themes['Miku Teal']->id])->save();
        }
        if (isset($b[2])) {
            $b[2]->forceFill(['theming_mode' => 'off', 'active_theming_id' => null])->save();
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

        // rating pembeli + penalti (pakai rumus service yang sama dengan aplikasi)
        $ratings = app(RatingService::class);
        User::where('role', 'buyer')->pluck('id')->each(fn ($id) => $ratings->recalcBuyer($id));
        ArtistProfile::pluck('id')->each(fn ($id) => $ratings->recalcArtist($id));
        Product::pluck('id')->each(fn ($id) => $ratings->recalcProduct($id));
    }
}
