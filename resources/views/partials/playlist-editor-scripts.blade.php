{{-- Shared editor JS. Include inside @push('scripts'). --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script type="module">

/* --- Playlist title --- */
const titleInput = document.getElementById('playlistTitle');
document.getElementById('saveTitle').addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    App.loading(btn, true);
    try {
        await App.put(titleInput.dataset.url, { title: titleInput.value.trim() || 'Audios' });
        App.toast('Nombre guardado', 'success');
    } catch { App.toast('No se pudo guardar', 'error'); }
    finally { App.loading(btn, false); }
});

/* --- Track title (save on blur, only if changed) --- */
document.querySelectorAll('.track-title').forEach(inp => {
    let original = inp.value;
    inp.addEventListener('blur', async () => {
        if (inp.value === original) return;
        try {
            const r = await App.put(inp.dataset.url, { title: inp.value.trim() });
            inp.value = r.title;
            original = r.title;
            App.toast('Audio renombrado', 'success');
        } catch { App.toast('No se pudo renombrar', 'error'); inp.value = original; }
    });
});

/* --- Delete track --- */
document.querySelectorAll('.track-delete').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('¿Eliminar este audio?')) return;
        try {
            await App.del(btn.dataset.url);
            btn.closest('.track-row').remove();
            App.toast('Audio eliminado', 'success');
        } catch { App.toast('No se pudo eliminar', 'error'); }
    });
});

/* --- Drag reorder --- */
const listEl = document.getElementById('trackList');
new Sortable(listEl, {
    handle: '.track-handle',
    animation: 150,
    ghostClass: 'dragging',
    onEnd: async () => {
        const ids = [...listEl.querySelectorAll('.track-row')].map(r => Number(r.dataset.id));
        try { await App.post(listEl.dataset.reorderUrl, { ids }); }
        catch { App.toast('No se pudo guardar el orden', 'error'); }
    },
});
</script>