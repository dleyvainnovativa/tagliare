@extends('layouts.app')

@section('title', 'Editar · ' . $playlist->title)

@section('body')
<div class="container py-4 py-md-5" style="max-width: 760px;">

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost mb-2">
                <i class="fa-solid fa-arrow-left me-1"></i> Mis listas
            </a>
            <h1 class="h4 fw-bold mb-0">Editar lista</h1>
        </div>
        <a href="{{ route('audio.show', $playlist) }}" target="_blank" class="btn btn-brand btn-sm">
            <i class="fa-solid fa-play me-1"></i> Abrir reproductor
        </a>
    </div>

    @include('partials.playlist-editor')
</div>
@endsection

@push('scripts')
@include('partials.playlist-editor-scripts')
@endpush