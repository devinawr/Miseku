<?php

namespace App\Livewire\Custom;

use App\Models\CustomRequest;
use App\Models\FileUpload;
use App\Models\MerchandiseType;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Form pengajuan custom (BPMN 4). Referensi gambar disimpan di file_upload (source_table custom_request). */
#[Layout('components.layouts.app')]
#[Title('Ajukan custom')]
class Create extends Component
{
    use WithFileUploads;

    public ?int $merchandise_type_id = null;

    public string $design_character = '';

    public int $quantity = 1;

    public string $specification = '';

    public $budget_price = null;

    public array $references = [];

    public function save(): void
    {
        $data = $this->validate([
            'merchandise_type_id' => 'required|exists:merchandise_type,id',
            'design_character'    => 'required|string|max:150',
            'quantity'            => 'required|integer|min:1|max:1000',
            'specification'       => 'required|string|min:10|max:3000',
            'budget_price'        => 'required|numeric|min:10000',
            'references'          => 'array|max:5',
            'references.*'        => 'image|max:4096',
        ], [], [
            'merchandise_type_id' => 'jenis merch', 'design_character' => 'karakter/desain', 'quantity' => 'jumlah',
            'specification' => 'spesifikasi', 'budget_price' => 'budget', 'references.*' => 'gambar referensi',
        ]);

        $req = CustomRequest::create([
            'buyer_id' => Auth::id(), 'merchandise_type_id' => $data['merchandise_type_id'], 'design_character' => $data['design_character'],
            'quantity' => $data['quantity'], 'specification' => $data['specification'], 'budget_price' => $data['budget_price'], 'request_status' => 'open',
        ]);

        foreach ($this->references as $i => $file) {
            FileUpload::create(['url' => '/uploads/' . $file->store('custom', 'uploads'), 'source_table' => 'custom_request', 'source_id' => $req->id, 'uploaded_by' => Auth::id(), 'sort_order' => $i + 1]);
        }

        // kabari semua artis aktif bahwa ada permintaan custom baru
        $notify = app(NotificationService::class);
        User::where('role', 'artist')->where('is_active', true)->whereHas('artistProfile')->pluck('id')
            ->each(fn ($id) => $notify->send($id, 'custom', 'Permintaan custom baru', "Ada permintaan custom \"{$req->design_character}\" ({$req->quantity} pcs). Beri penawaran di Studio → Custom.", null, false));

        session()->flash('status', 'Permintaan custom dikirim. Tunggu penawaran dari artis ya!');
        $this->redirectRoute('custom.show', ['customRequest' => $req->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.custom.create', ['types' => MerchandiseType::active()->orderBy('type_name')->get()]);
    }
}
