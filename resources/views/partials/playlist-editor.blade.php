{{-- Shared playlist editor: title rename, cover, track rename/reorder/delete.
     Expects $playlist. Used by upload.success and playlist edit views. --}}

{{-- Editable title --}}
<div class="panel panel-pad mb-3">
    <label class="form-label fw-semibold text-soft small text-uppercase mb-1" style="letter-spacing:.08em;">
        Nombre de la lista
    </label>
    <div class="d-flex gap-2">
        <input type="text" id="playlistTitle" class="form-control fw-semibold"
               value="{{ $playlist->title }}" maxlength="120"
               data-url="{{ route('playlist.update', $playlist) }}">
        <button class="btn btn-brand" id="saveTitle"><i class="fa-solid fa-check"></i></button>
    </div>
</div>

{{-- Cover --}}
<div class="panel panel-pad mb-3">
    <div class="d-flex align-items-center gap-3">
        <div class="cover-thumb {{ $playlist->hasCustomCover() ? '' : 'is-logo' }}">
            <img src="{{ $playlist->coverUrl() }}" alt="Portada">
        </div>
        <div class="flex-grow-1">
            <div class="eyebrow mb-1">Portada</div>
            <p class="text-soft small mb-2">
                @if ($playlist->hasCustomCover())
                    Imagen personalizada.
                @else
                    Usando el logo por defecto. Sube una imagen para personalizarla.
                @endif
            </p>
            <form id="coverForm" action="{{ route('playlist.cover', $playlist) }}"
                  method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                @csrf
                <input type="file" name="cover" id="coverInput" accept="image/*"
                       class="form-control form-control-sm" required>
                <button type="submit" class="btn btn-brand btn-sm flex-shrink-0">
                    <i class="fa-solid fa-upload"></i>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Track list --}}
<div class="panel panel-pad">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-bold mb-0">Audios <span class="text-soft fw-normal">({{ $playlist->audios->count() }})</span></h2>
        <span class="text-soft small"><i class="fa-solid fa-up-down me-1"></i> Arrastra para reordenar</span>
    </div>

    <div id="trackList" data-reorder-url="{{ route('playlist.reorder', $playlist) }}">
        @foreach ($playlist->audios as $audio)
            <div class="track-row" data-id="{{ $audio->id }}">
                <span class="track-handle"><i class="fa-solid fa-grip-vertical"></i></span>
                <input type="text" class="form-control form-control-sm border-0 px-1 track-title"
                       value="{{ $audio->displayName() }}" maxlength="160"
                       data-url="{{ route('audio.update', $audio) }}">
                <span class="font-mono small text-soft">{{ gmdate($audio->duration >= 3600 ? 'G:i:s' : 'i:s', $audio->duration) }}</span>
                <button class="btn btn-sm btn-ghost track-delete"
                        data-url="{{ route('audio.destroy', $audio) }}">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        @endforeach
    </div>
</div>
