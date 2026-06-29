@extends('layouts.app')

@section('title', 'Lista creada')

@section('body')
<div class="container py-5" style="max-width: 760px;">

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
        <span class="badge rounded-pill px-3 py-2" style="background: var(--accent-tint); color: var(--accent-600);">
            <i class="fa-solid fa-check me-1"></i> Lista creada
        </span>
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost">
            <i class="fa-solid fa-arrow-left me-1"></i> Mis listas
        </a>
    </div>

    {{-- QR + share link --}}
    <div class="panel panel-pad mb-3">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <div id="qrSlot" class="d-flex align-items-center justify-content-center"
                    style="width:120px;height:120px;background:var(--surface-2);border-radius:var(--radius);">
                    @if ($playlist->qrUrl())
                    <img src="{{ $playlist->qrUrl() }}" alt="Código QR" style="width:100%;height:100%;border-radius:var(--radius);">
                    @else
                    <i class="fa-solid fa-qrcode fa-2x text-soft"></i>
                    @endif
                </div>
                @if ($playlist->qrUrl())
                <a href="{{ route('upload.qr', $playlist) }}"
                    class="btn btn-ghost btn-sm w-100 mt-2">
                    <i class="fa-solid fa-download me-1"></i> QR
                </a>
                @endif
            </div>
            <div class="col">
                <div class="eyebrow mb-1">Enlace público</div>
                <div class="input-group">
                    <input type="text" id="shareLink" class="form-control font-mono small"
                        value="{{ route('audio.show', $playlist) }}" readonly>
                    <button class="btn btn-ghost" id="copyLink"><i class="fa-regular fa-copy"></i></button>
                </div>
                <a href="{{ route('audio.show', $playlist) }}" target="_blank"
                    class="btn btn-brand btn-sm mt-2">
                    <i class="fa-solid fa-play me-1"></i> Abrir reproductor
                </a>
            </div>
        </div>
    </div>

    @include('partials.playlist-editor')
</div>
@endsection

@push('scripts')
@include('partials.playlist-editor-scripts')
<script type="module">
    document.getElementById('copyLink').addEventListener('click', async () => {
        await navigator.clipboard.writeText(document.getElementById('shareLink').value);
        App.toast('Enlace copiado', 'success');
    });
</script>
@endpush