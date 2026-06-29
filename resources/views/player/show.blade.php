@extends('layouts.app')

@section('title', $playlist->title)

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('body')
<div class="player-stage">
    <main id="player"
        data-playlist-title="{{ $playlist->title }}"
        data-cover="{{ $playlist->coverUrl() }}">

        {{-- Cover --}}
        <div class="player-cover {{ $playlist->hasCustomCover() ? '' : 'is-logo' }}">
            <img src="{{ $playlist->coverUrl() }}" alt="Portada de {{ $playlist->title }}" data-cover>

            {{-- Now-playing equalizer --}}
            <div class="eq" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        </div>

        {{-- Body --}}
        <div class="player-body">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <div class="overflow-hidden">
                    <div class="eyebrow mb-1">{{ $playlist->title }}</div>
                    <h1 class="player-track-title" data-track-title>—</h1>
                </div>
                <span class="player-counter flex-shrink-0" data-counter></span>
            </div>

            {{-- Seek --}}
            <div class="seek-wrap" data-seek-wrap>
                <input type="range" class="seek-input" min="0" max="100" value="0" step="0.1"
                    data-seek aria-label="Progreso del audio">
                <div class="seek-track">
                    <div class="seek-fill" data-seek-fill></div>
                </div>
                <div class="seek-thumb" data-seek-thumb></div>
            </div>
            <div class="seek-times">
                <span data-current-time>0:00</span>
                <span data-duration>0:00</span>
            </div>

            {{-- Controls --}}
            <div class="player-controls">
                <button class="ctrl" data-prev aria-label="Anterior"><i class="fa-solid fa-backward-step"></i></button>
                <button class="ctrl ctrl-play" data-play aria-label="Reproducir o pausar"><i class="fa-solid fa-play"></i></button>
                <button class="ctrl" data-next aria-label="Siguiente"><i class="fa-solid fa-forward-step"></i></button>
            </div>
        </div>

        {{-- Track list --}}
        <div class="player-list">
            <div class="player-list-head">
                <span class="eyebrow">Audios</span>
                <span class="player-counter">{{ $playlist->audios->count() }} pistas</span>
            </div>
            <div data-list>
                @foreach ($playlist->audios as $i => $audio)
                <button type="button" class="player-row" data-row>
                    <span class="player-row-num">{{ $i + 1 }}</span>
                    <span class="player-row-name">{{ $audio->displayName() }}</span>
                    <span class="player-row-dur">{{ gmdate($audio->duration >= 3600 ? 'G:i:s' : 'i:s', $audio->duration) }}</span>
                </button>
                @endforeach
            </div>
        </div>

        <div class="player-footer">
            <i class="fa-solid fa-headphones me-1"></i> Tagliare Audios
        </div>
    </main>
</div>

{{-- Track data for the player --}}
@php
$trackData = $playlist->audios->map(fn ($a) => [
'src' => route('audio.stream', ['playlist' => $playlist->slug, 'audio' => $a->id]),
'title' => $a->displayName(),
'duration' => $a->duration,
]);
@endphp
<script type="application/json" id="playlist-data">
    @json($trackData)
</script>
@endsection

@push('scripts')
<script src="{{ Vite::asset('resources/js/player.js') }}" type="module"></script>
@endpush