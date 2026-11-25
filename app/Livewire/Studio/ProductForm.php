<?php

namespace App\Livewire\Studio;

use App\Models\CartItem;
use App\Models\DesignOption;
use App\Models\Fandom;
use App\Models\FandomCharacter;
use App\Models\FileUpload;
use App\Models\MerchandiseType;
use App\Models\PoBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\OrderItem;
use App\Models\CustomerOrder;
use App\Support\Fail;
use App\Services\NotificationService;
use App\Services\PollService;
use App\Services\PoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Studio artis — form upload / ubah produk.
 *
 * Satu form menulis ke 3 tabel:
 *  - product      : data produk (karakter → fandom otomatis, jenis merch, harga, stok)
 *  - po_batch     : bila pre-order, periode PO + skema DP/bayar penuh
 *  - file_upload  : 1–5 foto (source_table = 'product', sort_order 1 = sampul)
 * File foto disimpan ke public/uploads/products (disk "uploads").
 *
 * Usulan master: kalau fandom / karakter / jenis merch belum ada, artis bisa
 * mengusulkannya langsung dari form ini (status = 'pending', proposed_by = artis).
 * Usulan langsung bisa dipakai di produk, tapi produk baru tampil di katalog
 * setelah admin menyetujui usulan itu (lihat Product::scopeVisible).
 *
 * Dari voting desain: /studio/produk/baru?design_option={id} → form terisi sebagai PO,
 * product.design_option_id terisi, dan pemilih voting diberi notifikasi PO dibuka.
 * Produk baru yang langsung tampil juga diumumkan ke penggemar fandom-nya.
 * Mengubah produk PO menjadi ready stock menutup batch PO yang masih dibuka (PoService::closeNow).
 */
#[Layout('components.layouts.app')]
class ProductForm extends Component
{
    use WithFileUploads;

    public const MAX_PHOTOS = 5;

    public ?Product $product = null;

    /** Opsi desain pemenang voting yang dijadikan produk PO (opsional). */
    #[Url(as: 'design_option')]
    public ?int $designOptionId = null;

    // ---- Data produk ----
    public string $product_name = '';
    public string $description = '';
    public string $size = '';
    public string $price = '';
    public string $fandom_id = '';
    public string $fandom_character_id = '';
    public string $merchandise_type_id = '';
    public string $availability_type = 'ready_stock';
    public string $stock = '1';
    public bool $allow_event_pickup = false;
    public bool $publish = true;

    /** Varian aktif; stock adalah kuota per batch untuk produk PO. */
    public array $variants = [];
    public array $variantPhotos = [];

    public function addVariant(): void
    {
        if (count($this->variants) < 20) $this->variants[] = ['id' => null, 'name' => '', 'stock' => '0', 'original_stock' => null, 'image_url' => null];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index], $this->variantPhotos[$index]);
        $old = $this->variants; $photos = $this->variantPhotos;
        $this->variants = []; $this->variantPhotos = [];
        foreach ($old as $key => $row) {
            $new = count($this->variants); $this->variants[] = $row;
            if (isset($photos[$key])) $this->variantPhotos[$new] = $photos[$key];
        }
    }

    // ---- Batch PO ----
    public string $payment_scheme = 'full';
    public string $dp_amount = '';
    public string $closes_at = '';
    public string $estimated_month = '';
    public string $min_order = '';
    public string $max_slot = '';

    // ---- Foto ----
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $photos = [];
    public array $newPhotos = [];
    /** id file_upload lama yang dipertahankan, urut = urutan tampil */
    public array $keptImageIds = [];

    // ---- Usulan master baru ----
    /** 'fandom' | 'character' | 'type' | null — form usulan yang sedang dibuka */
    public ?string $proposing = null;
    public string $proposalName = '';

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            abort_unless(
                $product->artist_profile_id === Auth::user()->artistProfile->id && $product->product_status !== 'deleted',
                404
            );

            $product->load(['character', 'images', 'openBatch', 'variants']);
            $this->product = $product;

            $this->fill([
                'product_name'        => $product->product_name,
                'description'         => (string) $product->description,
                'size'                => (string) $product->size,
                'price'               => (string) (int) $product->price,
                'fandom_id'           => (string) $product->character->fandom_id,
                'fandom_character_id' => (string) $product->fandom_character_id,
                'merchandise_type_id' => (string) $product->merchandise_type_id,
                'availability_type'   => $product->availability_type,
                'stock'               => (string) $product->stock,
                'allow_event_pickup'  => (bool) $product->allow_event_pickup,
                'publish'             => $product->product_status === 'active',
                'keptImageIds'        => $product->images->pluck('id')->all(),
            ]);

            $this->variants = $product->variants->map(fn ($v) => [
                'id' => $v->id, 'name' => $v->name, 'stock' => (string) $v->stock,
                'original_stock' => $v->stock, 'image_url' => $v->image_url,
            ])->all();

            $batch = $product->batches()->where('batch_status', 'open')->latest('id')->first();
            if ($batch) {
                $this->fill([
                    'payment_scheme'  => $batch->payment_scheme,
                    'dp_amount'       => $batch->dp_amount ? (string) (int) $batch->dp_amount : '',
                    'closes_at'       => $batch->closes_at->format('Y-m-d'),
                    'estimated_month' => $batch->estimated_month?->format('Y-m') ?? '',
                    'min_order'       => (string) ($batch->min_order ?? ''),
                    'max_slot'        => (string) ($batch->max_slot ?? ''),
                ]);
            }
        } else {
            $this->product = null;
            $this->closes_at = now()->addDays(14)->format('Y-m-d');
            $this->estimated_month = now()->addMonths(2)->format('Y-m');

            if ($option = $this->designOption) {
                $this->availability_type = 'pre_order';
                $this->product_name = mb_substr($option->poll->poll_title, 0, 150);
                $this->description = trim('Desain pemenang voting "' . $option->poll->poll_title . '".' . ($option->description ? "\n\n" . $option->description : ''));
            } else {
                $this->designOptionId = null;
            }
        }
    }

    /** Opsi desain milik artis ini, dari voting yang sudah selesai & belum jadi produk. */
    #[Computed]
    public function designOption(): ?DesignOption
    {
        if (! $this->designOptionId || $this->product) {
            return null;
        }

        return DesignOption::with('poll')
            ->whereKey($this->designOptionId)
            ->whereHas('poll', fn ($q) => $q->where('artist_profile_id', Auth::user()->artistProfile->id)->where('poll_status', 'completed'))
            ->whereDoesntHave('product')
            ->first();
    }

    // ---------- Pilihan dropdown ----------

    /** Master yang boleh dipilih: sudah disetujui, atau masih menunggu persetujuan. */
    private const SELECTABLE = ['active', 'pending'];

    #[Computed]
    public function fandoms()
    {
        return Fandom::whereIn('status', self::SELECTABLE)->orderBy('fandom_name')->get(['id', 'fandom_name', 'status']);
    }

    #[Computed]
    public function characters()
    {
        if ($this->fandom_id === '') {
            return collect();
        }

        return FandomCharacter::whereIn('status', self::SELECTABLE)
            ->where('fandom_id', $this->fandom_id)
            ->orderBy('character_name')
            ->get(['id', 'character_name', 'status']);
    }

    #[Computed]
    public function types()
    {
        return MerchandiseType::whereIn('status', self::SELECTABLE)->orderBy('type_name')->get(['id', 'type_name', 'status']);
    }

    // ---------- Usulan fandom / karakter / jenis baru ----------

    public function startProposal(string $kind): void
    {
        abort_unless(in_array($kind, ['fandom', 'character', 'type'], true), 400);

        if ($kind === 'character' && $this->fandom_id === '') {
            $this->addError('fandom_id', 'Pilih atau usulkan fandom dulu sebelum mengusulkan karakter.');

            return;
        }

        $this->proposing = $kind;
        $this->proposalName = '';
        $this->resetErrorBag('proposalName');
    }

    public function cancelProposal(): void
    {
        $this->proposing = null;
        $this->proposalName = '';
        $this->resetErrorBag('proposalName');
    }

    /**
     * Simpan usulan. Kalau nama yang sama (tanpa beda huruf besar/kecil) sudah ada:
     *  - active / pending → langsung dipilih, tidak dibuat duplikat
     *  - rejected         → ditolak, karena admin sudah pernah menolaknya
     */
    public function submitProposal(): void
    {
        $this->validate(
            ['proposalName' => ['required', 'string', 'min:2', $this->proposing === 'type' ? 'max:80' : 'max:120']],
            [],
            ['proposalName' => ['fandom' => 'nama fandom', 'character' => 'nama karakter', 'type' => 'nama jenis merchandise'][$this->proposing] ?? 'nama']
        );

        $name = preg_replace('/\s+/', ' ', trim($this->proposalName));

        [$model, $field, $target, $scope] = match ($this->proposing) {
            'fandom'    => [Fandom::class, 'fandom_name', 'fandom_id', []],
            'character' => [FandomCharacter::class, 'character_name', 'fandom_character_id', ['fandom_id' => (int) $this->fandom_id]],
            'type'      => [MerchandiseType::class, 'type_name', 'merchandise_type_id', []],
            default     => abort(400),
        };

        $existing = $model::query()->where($scope)->whereRaw("LOWER({$field}) = ?", [mb_strtolower($name)])->first();

        if ($existing?->status === 'rejected') {
            $this->addError('proposalName', "“{$existing->{$field}}” sudah pernah ditolak admin. Pilih yang lain dari daftar.");

            return;
        }

        $item = $existing ?? $model::create($scope + [
            $field        => $name,
            'status'      => 'pending',
            'proposed_by' => Auth::id(),
        ]);

        if ($this->proposing === 'fandom' && $this->fandom_id !== (string) $item->id) {
            $this->fandom_character_id = '';
        }
        $this->{$target} = (string) $item->id;

        unset($this->fandoms, $this->characters, $this->types);

        $this->dispatch('toast', message: $existing
            ? "“{$item->{$field}}” sudah ada, langsung dipilih."
            : "Usulan “{$name}” dikirim ke admin. Produk tampil di katalog setelah disetujui.");

        $this->cancelProposal();
    }

    #[Computed]
    public function keptImages()
    {
        if (! $this->product || ! $this->keptImageIds) {
            return collect();
        }

        $images = FileUpload::where('source_table', 'product')
            ->where('source_id', $this->product->id)
            ->whereIn('id', $this->keptImageIds)
            ->get()->keyBy('id');

        return collect($this->keptImageIds)->map(fn ($id) => $images->get($id))->filter()->values();
    }

    public function updatedFandomId(): void
    {
        $this->fandom_character_id = '';
    }

    /** File baru dipilih → gabungkan ke antrean (maks 5 total). */
    public function updatedNewPhotos(): void
    {
        $this->validate([
            'newPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], ['newPhotos.*' => 'foto']);

        $room = self::MAX_PHOTOS - count($this->keptImageIds) - count($this->photos);
        $this->photos = array_merge($this->photos, array_slice($this->newPhotos, 0, max(0, $room)));

        if (count($this->newPhotos) > $room) {
            $this->dispatch('toast', message: 'Maksimal '.self::MAX_PHOTOS.' foto per produk.', tone: 'error');
        }

        $this->newPhotos = [];
        $this->resetErrorBag('photos');
    }

    public function removeNewPhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function removeKeptImage(int $id): void
    {
        $this->keptImageIds = array_values(array_diff($this->keptImageIds, [$id]));
    }

    public function makeCover(int $id): void
    {
        $this->keptImageIds = array_values(array_merge([$id], array_diff($this->keptImageIds, [$id])));
    }

    // ---------- Validasi ----------

    protected function rules(): array
    {
        $isPo = $this->availability_type === 'pre_order';

        return [
            'product_name'        => ['required', 'string', 'min:5', 'max:150'],
            'description'         => ['nullable', 'string', 'max:5000'],
            'size'                => ['nullable', 'string', 'max:60'],
            'price'               => ['required', 'integer', 'min:1000', 'max:99999999'],
            'fandom_id'           => ['required', Rule::exists('fandom', 'id')->whereIn('status', self::SELECTABLE)],
            'fandom_character_id' => ['required', Rule::exists('fandom_character', 'id')->where('fandom_id', $this->fandom_id)->whereIn('status', self::SELECTABLE)],
            'merchandise_type_id' => ['required', Rule::exists('merchandise_type', 'id')->whereIn('status', self::SELECTABLE)],
            'availability_type'   => ['required', Rule::in(['ready_stock', 'pre_order'])],
            'stock'               => [$isPo || count($this->variants) ? 'nullable' : 'required', 'integer', 'min:0', 'max:99999'],
            'allow_event_pickup'  => ['boolean'],
            'publish'             => ['boolean'],

            'payment_scheme'  => [$isPo ? 'required' : 'nullable', Rule::in(['dp', 'full'])],
            'dp_amount'       => [$isPo && $this->payment_scheme === 'dp' ? 'required' : 'nullable', 'integer', 'min:1000', 'lt:price'],
            'closes_at'       => [$isPo ? 'required' : 'nullable', 'date', 'after_or_equal:today', 'before:+1 year'],
            'estimated_month' => [$isPo ? 'required' : 'nullable', 'date_format:Y-m'],
            'min_order'       => ['nullable', 'integer', 'min:1', 'max:99999'],
            'max_slot'        => ['nullable', 'integer', 'min:1', 'max:99999', ...($this->min_order !== '' ? ['gte:min_order'] : [])],

            'variants' => ['array', 'max:20'],
            'variants.*.name' => ['required', 'string', 'min:1', 'max:100', 'distinct'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:99999'],
            'variantPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'product_name' => 'nama produk', 'description' => 'deskripsi', 'size' => 'ukuran', 'price' => 'harga',
            'fandom_id' => 'fandom', 'fandom_character_id' => 'karakter', 'merchandise_type_id' => 'jenis merchandise',
            'stock' => 'stok', 'payment_scheme' => 'skema pembayaran', 'dp_amount' => 'nominal DP',
            'closes_at' => 'tanggal PO ditutup', 'estimated_month' => 'estimasi barang jadi',
            'min_order' => 'minimum produksi', 'max_slot' => 'kuota pcs', 'photos.*' => 'foto',
        ];
    }

    protected function messages(): array
    {
        return [
            'dp_amount.lt' => 'Nominal DP harus lebih kecil dari harga.',
            'max_slot.gte' => 'Kuota pcs tidak boleh lebih kecil dari minimum produksi.',
        ];
    }

    public function updated($property): void
    {
        if (! in_array($property, ['newPhotos', 'photos', 'proposalName'], true) && ! str_starts_with($property, 'newPhotos') && ! str_starts_with($property, 'variantPhotos') && ! str_starts_with($property, 'variants')) {
            $this->validateOnly($property);
        }
    }

    // ---------- Simpan ----------

    public function save()
    {
        $data = $this->validate();

        if ($this->estimated_month !== '' && $this->availability_type === 'pre_order'
            && Carbon::createFromFormat('Y-m-d', $this->estimated_month.'-01')->endOfMonth()->lt(Carbon::parse($this->closes_at))) {
            $this->addError('estimated_month', 'Estimasi barang jadi tidak boleh sebelum PO ditutup.');

            return null;
        }

        if (count($this->keptImageIds) + count($this->photos) < 1) {
            $this->addError('photos', 'Upload minimal 1 foto produk.');

            return null;
        }

        foreach ($this->variants as $i => $row) {
            if (! isset($this->variantPhotos[$i]) && empty($row['id'])) {
                $this->addError('variantPhotos.'.$i, 'Foto varian wajib diunggah.'); return null;
            }
        }
        $shop = Auth::user()->artistProfile;
        $isPo = $this->availability_type === 'pre_order';
        $isNew = ! $this->product;

        $product = DB::transaction(function () use ($data, $shop, $isPo) {
            $product = $this->product ? Product::whereKey($this->product->id)->where('artist_profile_id', $shop->id)->lockForUpdate()->firstOrFail()
                : new Product(['artist_profile_id' => $shop->id]);
            if ($product->exists) {
                $oldIds = $product->variants()->pluck('id')->map(fn ($id) => (int) $id)->all();
                $newIds = collect($this->variants)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
                Fail::unless(! array_diff($newIds, $oldIds), 'Varian tidak valid. Muat ulang halaman.');
                $changesStructure = (bool) $oldIds !== (bool) $this->variants || count(array_diff($oldIds, $newIds)) > 0
                    || $product->availability_type !== $data['availability_type'];
                if ($changesStructure) {
                    $active = OrderItem::where('product_id', $product->id)->whereHas('order', fn ($q) => $q->whereIn('order_status', CustomerOrder::ACTIVE))->exists();
                    Fail::unless(! $active, 'Selesaikan atau batalkan pesanan aktif sebelum menghapus varian atau mengubah jenis produk.');
                }
            }

            if (! $this->product && $this->designOption) {
                $product->design_option_id = $this->designOption->id;
            }

            $product->fill([
                'fandom_character_id' => (int) $data['fandom_character_id'],
                'merchandise_type_id' => (int) $data['merchandise_type_id'],
                'product_name'        => trim($data['product_name']),
                'description'         => trim((string) $data['description']) ?: null,
                'size'                => trim((string) $data['size']) ?: null,
                'price'               => (int) $data['price'],
                'stock'               => $isPo || count($this->variants) ? 0 : (int) $data['stock'],
                'availability_type'   => $data['availability_type'],
                'allow_event_pickup'  => (bool) $data['allow_event_pickup'],
                'product_status'      => $data['publish'] ? 'active' : 'inactive',
            ])->save();

            $this->syncVariants($product, $isPo);
            $this->syncBatch($product, $isPo);
            $this->syncPhotos($product);

            // item keranjang yang tak lagi valid dibiarkan — halaman keranjang menandainya.
            return $product;
        });

        $waiting = $product->unapprovedMasters();

        if ($isNew && $product->product_status === 'active') {
            if (! $waiting) {
                app(NotificationService::class)->announceNewProduct($product);
            }
        }
        session()->flash('status', ($isNew ? 'Produk berhasil diupload.' : 'Perubahan produk disimpan.')
            . ($waiting ? ' Produk tampil di katalog setelah admin menyetujui usulan ' . collect($waiting)->pluck('name')->join(', ', ' & ') . '.' : ''));

        return $this->redirect(route('studio.dashboard'), navigate: true);
    }

    private function syncVariants(Product $product, bool $isPo): void
    {
        $existing = $product->variants()->lockForUpdate()->get()->keyBy('id');
        $kept = []; $total = 0;
        foreach ($this->variants as $i => $row) {
            $variant = ! empty($row['id']) ? $existing->get((int) $row['id']) : new ProductVariant(['product_id' => $product->id]);
            Fail::unless((bool) $variant, 'Varian tidak valid.');
            $stock = (int) $row['stock'];
            if ($variant->exists && $variant->stock !== (int) ($row['original_stock'] ?? -1)) {
                Fail::unless($stock === (int) ($row['original_stock'] ?? -1), 'Stok berubah sejak halaman dibuka. Muat ulang sebelum mengubah stok.');
                $stock = $variant->stock;
            }
            if ($isPo && $variant->exists && ($batch = $product->openBatch)) {
                $already = $variant->stock - $variant->remainingForBatch($batch);
                Fail::unless($stock >= $already, 'Kuota varian tidak boleh kurang dari jumlah yang sudah dipesan.');
            }
            $url = $variant->image_url;
            if (isset($this->variantPhotos[$i])) $url = '/uploads/'.$this->variantPhotos[$i]->store('products/variants', 'uploads');
            Fail::unless((bool) $url, 'Foto varian wajib diunggah.');
            $variant->fill(['name' => trim($row['name']), 'stock' => $stock, 'image_url' => $url, 'is_active' => true])->save();
            $kept[] = $variant->id; $total += $stock;
        }
        if ($existing->isNotEmpty()) ProductVariant::whereIn('id', $existing->keys()->diff($kept))->update(['is_active' => false]);
        if (! $isPo && $this->variants) $product->update(['stock' => $total]);
    }

    private function syncBatch(Product $product, bool $isPo): void
    {
        $open = $product->batches()->where('batch_status', 'open')->latest('id')->first();

        if (! $isPo) {
            // berubah jadi ready stock → batch PO yang masih dibuka ditutup lewat aturan PO
            // (target tercapai → menunggu produksi, tidak tercapai → batal + refund)
            if ($open) {
                app(PoService::class)->closeNow($open);
            }

            return;
        }

        $values = [
            'price_po'        => (int) $this->price,
            'payment_scheme'  => $this->payment_scheme,
            'dp_amount'       => $this->payment_scheme === 'dp' ? (int) $this->dp_amount : null,
            'min_order'       => $this->min_order !== '' ? (int) $this->min_order : null,
            'max_slot'        => $this->max_slot !== '' ? (int) $this->max_slot : null,
            'closes_at'       => Carbon::parse($this->closes_at)->endOfDay(),
            'estimated_month' => $this->estimated_month.'-01',
        ];

        if ($open) {
            $open->update($values);
        } else {
            PoBatch::create($values + [
                'product_id'   => $product->id,
                'opens_at'     => now(),
                'batch_status' => 'open',
            ]);
        }
    }

    private function syncPhotos(Product $product): void
    {
        // hapus foto lama yang dibuang artis
        $removed = FileUpload::where('source_table', 'product')
            ->where('source_id', $product->id)
            ->whereNotIn('id', $this->keptImageIds ?: [0])
            ->get();

        foreach ($removed as $file) {
            if (str_starts_with($file->url, '/uploads/')) {
                Storage::disk('uploads')->delete(substr($file->url, strlen('/uploads/')));
            }
            $file->delete();
        }

        $order = 1;
        foreach ($this->keptImageIds as $id) {
            FileUpload::whereKey($id)->update(['sort_order' => $order++]);
        }

        foreach ($this->photos as $photo) {
            $path = $photo->store('products', 'uploads');

            FileUpload::create([
                'url'          => '/uploads/'.$path,
                'source_table' => 'product',
                'source_id'    => $product->id,
                'uploaded_by'  => Auth::id(),
                'sort_order'   => $order++,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.studio.product-form')
            ->title($this->product ? 'Ubah produk' : 'Upload produk');
    }
}
