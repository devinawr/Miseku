@if ($photos->isNotEmpty())
    <div class="mt-2 flex flex-wrap gap-2" aria-label="Foto ulasan">
        @foreach ($photos as $photo)
            <a href="{{ $photo->url }}" target="_blank" rel="noopener noreferrer" aria-label="Buka foto ulasan ukuran penuh">
                <img src="{{ $photo->url }}" alt="Foto ulasan" loading="lazy" class="h-20 w-20 rounded-lg object-cover">
            </a>
        @endforeach
    </div>
@endif
