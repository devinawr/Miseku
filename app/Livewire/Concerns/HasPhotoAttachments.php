<?php

namespace App\Livewire\Concerns;

use App\Models\FileUpload;
use Illuminate\Support\Facades\Auth;

/**
 * Lampiran foto untuk pesan (chat 1:1, custom, komunitas).
 * Foto disimpan di tabel file_upload yang sudah ada (polimorfik:
 * source_table = 'chat_message' / 'topic_message', source_id = id pesan) → ERD tidak berubah.
 * Komponen yang memakai trait ini juga wajib `use WithFileUploads`.
 */
trait HasPhotoAttachments
{
    /** Foto sementara (TemporaryUploadedFile) yang akan dikirim. */
    public array $photos = [];

    protected int $maxPhotos = 4;

    protected function photoRules(): array
    {
        return [
            'photos'   => ['array', 'max:' . $this->maxPhotos],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    protected function photoMessages(): array
    {
        return [
            'photos.max'     => 'Maksimal ' . $this->maxPhotos . ' foto per pesan.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'photos.*.max'   => 'Ukuran foto maksimal 4 MB.',
        ];
    }

    public function updatedPhotos(): void
    {
        $this->validate($this->photoRules(), $this->photoMessages());
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    /** Simpan foto ke public/uploads/{folder} lalu catat di file_upload. */
    protected function storePhotos(string $sourceTable, int $sourceId, string $folder = 'chat'): void
    {
        foreach (array_values($this->photos) as $i => $file) {
            FileUpload::create([
                'url'          => '/uploads/' . $file->store($folder, 'uploads'),
                'source_table' => $sourceTable,
                'source_id'    => $sourceId,
                'uploaded_by'  => Auth::id(),
                'sort_order'   => $i + 1,
            ]);
        }
        $this->photos = [];
    }
}