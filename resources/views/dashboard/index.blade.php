@extends('layouts.app')

@section('title', 'Mis listas de audios')

@section('body')
<div class="container py-4 py-md-5" style="max-width: 920px;">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="eyebrow mb-1">Panel</div>
            <h1 class="h3 fw-bold mb-0">Mis listas de audios</h1>
        </div>
        <a href="{{ route('upload.create') }}" class="btn btn-brand">
            <i class="fa-solid fa-plus me-1"></i> Nueva lista
        </a>
    </div>

    @if ($playlists->isEmpty())
        {{-- Empty state --}}
        <div class="panel panel-pad text-center py-5">
            <div class="mb-3" style="font-size: 2.5rem; color: var(--brand);">
                <i class="fa-solid fa-compact-disc"></i>
            </div>
            <h2 class="h5 fw-bold mb-2">Aún no tienes listas</h2>
            <p class="text-muted-2 mb-4">Sube tus audios de WhatsApp y crea tu primera lista para compartir.</p>
            <a href="{{ route('upload.create') }}" class="btn btn-brand btn-lg">
                <i class="fa-solid fa-plus me-1"></i> Crear mi primera lista
            </a>
        </div>
    @else
        {{-- Search --}}
        <div class="position-relative mb-3">
            <i class="fa-solid fa-magnifying-glass position-absolute text-soft"
               style="left: 14px; top: 50%; transform: translateY(-50%);"></i>
            <input type="text" id="searchInput" class="form-control ps-5"
                   placeholder="Buscar por nombre…" autocomplete="off">
        </div>

        {{-- List --}}
        <div class="panel" id="playlistList">
            {{-- Desktop header (hidden on mobile) --}}
            <div class="dash-head d-none d-md-flex">
                <span class="flex-grow-1">Lista</span>
                <span style="width: 90px;">Audios</span>
                <span style="width: 110px;">Creada</span>
                <span style="width: 160px;" class="text-end">Acciones</span>
            </div>

            @foreach ($playlists as $playlist)
                <div class="dash-row" data-title="{{ Str::lower($playlist->title) }}">
                    {{-- Cover + title --}}
                    <div class="dash-main">
                        <div class="dash-cover {{ $playlist->hasCustomCover() ? '' : 'is-logo' }}">
                            <img src="{{ $playlist->coverUrl() }}" alt="">
                        </div>
                        <div class="overflow-hidden">
                            <div class="dash-title text-truncate">{{ $playlist->title }}</div>
                            <a href="{{ route('audio.show', $playlist) }}" target="_blank"
                               class="dash-link font-mono text-truncate d-block">
                                /audio/{{ $playlist->slug }}
                            </a>
                        </div>
                    </div>

                    {{-- Meta --}}
                    <div class="dash-meta" style="width: 90px;">
                        <span class="d-md-none text-soft small me-1">Audios:</span>
                        {{ $playlist->audios_count }}
                    </div>
                    <div class="dash-meta" style="width: 110px;">
                        <span class="d-md-none text-soft small me-1">Creada:</span>
                        {{ $playlist->created_at->format('d/m/Y') }}
                    </div>

                    {{-- Actions --}}
                    <div class="dash-actions">
                        <a href="{{ route('audio.show', $playlist) }}" target="_blank"
                           class="btn btn-sm btn-ghost" title="Abrir reproductor" aria-label="Abrir reproductor">
                            <i class="fa-solid fa-play"></i>
                        </a>
                        <a href="{{ route('playlist.edit', $playlist) }}"
                           class="btn btn-sm btn-ghost" title="Editar" aria-label="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <button class="btn btn-sm btn-ghost dash-copy" data-link="{{ route('audio.show', $playlist) }}"
                                title="Copiar enlace" aria-label="Copiar enlace">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                        <a href="{{ route('upload.qr', $playlist) }}"
                           class="btn btn-sm btn-ghost" title="Descargar QR" aria-label="Descargar QR">
                            <i class="fa-solid fa-qrcode"></i>
                        </a>
                        <button class="btn btn-sm btn-ghost dash-delete"
                                data-url="{{ route('playlist.destroy', $playlist) }}"
                                data-title="{{ $playlist->title }}"
                                title="Eliminar" aria-label="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach

            {{-- No-results (shown by search JS) --}}
            <div id="noResults" class="text-center text-soft py-4 d-none">
                No se encontraron listas con ese nombre.
            </div>
        </div>
    @endif
</div>

{{-- Delete confirmation modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--radius-lg);">
            <div class="modal-body p-4">
                <h2 class="h5 fw-bold mb-2">¿Eliminar esta lista?</h2>
                <p class="text-muted-2 mb-1">
                    Se eliminará <strong id="deleteName"></strong> y todos sus audios de forma permanente.
                </p>
                <p class="text-soft small mb-4">Esta acción no se puede deshacer.</p>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-brand" id="confirmDelete" style="background: var(--danger); border-color: var(--danger);">
                        <i class="fa-solid fa-trash me-1"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">

/* --- Search (client-side filter) --- */
const search = document.getElementById('searchInput');
if (search) {
    const rows = [...document.querySelectorAll('.dash-row')];
    const noResults = document.getElementById('noResults');
    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach(row => {
            const match = row.dataset.title.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        noResults.classList.toggle('d-none', visible > 0);
    });
}

/* --- Copy link --- */
document.querySelectorAll('.dash-copy').forEach(btn => {
    btn.addEventListener('click', async () => {
        await navigator.clipboard.writeText(btn.dataset.link);
        App.toast('Enlace copiado', 'success');
    });
});

/* --- Delete (modal confirm) --- */
const modalEl = document.getElementById('deleteModal');
const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
const confirmBtn = document.getElementById('confirmDelete');
let pendingRow = null;
let pendingUrl = null;

document.querySelectorAll('.dash-delete').forEach(btn => {
    btn.addEventListener('click', () => {
        pendingRow = btn.closest('.dash-row');
        pendingUrl = btn.dataset.url;
        document.getElementById('deleteName').textContent = btn.dataset.title;
        modal.show();
    });
});

confirmBtn.addEventListener('click', async () => {
    if (!pendingUrl) return;
    App.loading(confirmBtn, true);
    try {
        await App.del(pendingUrl);
        pendingRow?.remove();
        modal.hide();
        App.toast('Lista eliminada', 'success');
    } catch {
        App.toast('No se pudo eliminar', 'error');
    } finally {
        App.loading(confirmBtn, false);
    }
});
</script>
@endpush
