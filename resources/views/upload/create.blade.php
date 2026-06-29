@extends('layouts.app')

@section('title', 'Subir audios')

@section('body')
<div class="container py-5" style="max-width: 720px;">

    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost mb-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Mis listas
        </a>
        <div class="eyebrow mb-1">Nueva lista</div>
        <h1 class="h3 fw-bold mb-1">Subir audios</h1>
        <p class="text-muted-2 mb-0">Agrega tus audios de WhatsApp, ponles nombre y obtén un enlace para compartir.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="uploadForm" action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="panel panel-pad mb-3">
            <label for="title" class="form-label fw-semibold">Nombre de la lista</label>
            <input type="text" class="form-control" id="title" name="title"
                   placeholder="Ej. Mensajes de la abuela" maxlength="120" value="{{ old('title') }}">
        </div>

        <div class="panel panel-pad mb-3">
            <label class="form-label fw-semibold">Imagen de portada <span class="text-soft fw-normal">(opcional)</span></label>
            <input type="file" class="form-control" id="cover" name="cover" accept="image/*">
        </div>

        <div class="panel panel-pad mb-3">
            <label class="form-label fw-semibold mb-2">Audios</label>

            <div class="dropzone" id="dropzone">
                <i class="fa-solid fa-music fa-lg mb-2 d-block" style="color: var(--brand);"></i>
                <div class="fw-medium">Arrastra tus audios aquí</div>
                <div class="text-soft small">o haz clic para elegir · m4a, mp3, wav, opus, ogg, aac</div>
                <input type="file" id="files" name="files[]" accept=".m4a,.mp3,.wav,.opus,.ogg,.aac,audio/*,video/mp4" multiple hidden>
            </div>

            <div id="fileList" class="mt-3"></div>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-brand w-100 py-2" disabled>
            <i class="fa-solid fa-arrow-up-from-bracket me-1"></i> Subir y crear lista
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script type="module">

const dropzone = document.getElementById('dropzone');
const input    = document.getElementById('files');
const list     = document.getElementById('fileList');
const submit   = document.getElementById('submitBtn');
const form     = document.getElementById('uploadForm');

// Plain arrays — no DataTransfer (iOS Safari can't assign input.files reliably).
let files = [];   // File objects
let names = [];   // display names, index-matched to files

dropzone.addEventListener('click', () => input.click());
['dragenter', 'dragover'].forEach(e =>
    dropzone.addEventListener(e, ev => { ev.preventDefault(); dropzone.classList.add('dragover'); }));
['dragleave', 'drop'].forEach(e =>
    dropzone.addEventListener(e, ev => { ev.preventDefault(); dropzone.classList.remove('dragover'); }));

dropzone.addEventListener('drop', ev => addFiles(ev.dataTransfer.files));
input.addEventListener('change', () => {
    addFiles(input.files);
    input.value = ''; // allow re-selecting the same file
});

function stripExt(name) {
    return name.replace(/\.[^/.]+$/, '');
}

function addFiles(fileList) {
    [...fileList].forEach(f => {
        files.push(f);
        names.push(stripExt(f.name));
    });
    sync();
    render();
}

function removeAt(idx) {
    files.splice(idx, 1);
    names.splice(idx, 1);
    sync();
    render();
}

function sync() {
    submit.disabled = files.length === 0;
}

function render() {
    list.innerHTML = '';
    files.forEach((f, i) => {
        const row = document.createElement('div');
        row.className = 'track-row';
        row.innerHTML = `
            <i class="fa-solid fa-file-audio text-soft"></i>
            <input type="text" class="form-control form-control-sm border-0 px-1 flex-grow-1 row-name"
                   value="${escapeHtml(names[i])}" maxlength="160" placeholder="Nombre del audio">
            <span class="font-mono small text-soft">${(f.size/1024/1024).toFixed(1)} MB</span>
            <button type="button" class="btn btn-sm btn-ghost" data-i="${i}">
                <i class="fa-solid fa-xmark"></i>
            </button>`;
        row.querySelector('.row-name').addEventListener('input', (e) => {
            names[i] = e.target.value;
        });
        row.querySelector('button').addEventListener('click', () => removeAt(i));
        list.appendChild(row);
    });
}

function escapeHtml(s) {
    return s.replace(/[&<>"']/g, c =>
        ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

// Build FormData by hand and POST via fetch — works on iOS Safari.
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!files.length) return;

    App.loading(submit, true);

    const fd = new FormData();
    fd.append('_token', '{{ csrf_token() }}');

    const title = document.getElementById('title');
    if (title && title.value.trim()) fd.append('title', title.value.trim());

    const cover = document.getElementById('cover');
    if (cover && cover.files[0]) fd.append('cover', cover.files[0]);

    files.forEach((f, i) => {
        fd.append('files[]', f, f.name);
        fd.append('names[]', (names[i] || '').trim());
    });

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd,
        });

        if (!res.ok) {
            let msg = 'No se pudo subir. Revisa los archivos e intenta de nuevo.';
            try {
                const data = await res.json();
                if (data.message) msg = data.message;
                if (data.errors) msg = Object.values(data.errors).flat()[0] || msg;
            } catch (_) {}
            App.toast(msg, 'error');
            App.loading(submit, false);
            return;
        }

        const data = await res.json();
        window.location.href = data.redirect; // success page
    } catch (err) {
        App.toast('Error de red. Intenta de nuevo.', 'error');
        App.loading(submit, false);
    }
});
</script>
@endpush
