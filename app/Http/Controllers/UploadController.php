<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Playlist;
use App\Services\AudioUploadService;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function __construct(
        protected AudioUploadService $uploads,
        protected QrCodeService $qr,
    ) {}

    /** The upload wizard (audios.mysite.com). */
    public function create(): View
    {
        return view('upload.create');
    }

    /** Handle the initial multi-file upload → new playlist. */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $allowed = AudioUploadService::ALLOWED_EXT;

        $validated = $request->validate([
            'title'   => ['nullable', 'string', 'max:120'],
            'cover'   => ['nullable', 'image', 'max:4096'], // 4MB
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:25600', 'extensions:' . implode(',', $allowed)], // 25MB each
            'names'   => ['nullable', 'array'],
            'names.*' => ['nullable', 'string', 'max:160'],
        ], [
            'files.required'      => 'Add at least one audio file.',
            'files.*.extensions'  => 'Allowed formats: ' . implode(', ', $allowed) . '.',
        ]);

        $playlist = $this->uploads->createPlaylist(
            files: $request->file('files'),
            title: $validated['title'] ?? null,
            cover: $request->file('cover'),
            names: $validated['names'] ?? [],
        );

        $this->qr->generate($playlist);

        $redirect = route('upload.success', $playlist);

        // The create form submits via fetch (iOS Safari compatibility).
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['redirect' => $redirect]);
        }

        return redirect($redirect);
    }

    /** Success screen — QR generated here in Tier 3. */
    public function success(Playlist $playlist): View
    {
        $playlist->load('audios');
        return view('upload.success', compact('playlist'));
    }

    /** Edit an existing playlist (reached from the dashboard). */
    public function edit(Playlist $playlist): View
    {
        $playlist->load('audios');
        return view('playlist.edit', compact('playlist'));
    }

    /** Replace the playlist cover image. */
    public function updateCover(Request $request, Playlist $playlist): RedirectResponse
    {
        $request->validate([
            'cover' => ['required', 'image', 'max:4096'],
        ]);

        // Remove the previous custom cover if there was one
        if ($playlist->cover_image) {
            Storage::disk('public')->delete($playlist->cover_image);
        }

        $playlist->update([
            'cover_image' => $request->file('cover')->store('covers', 'public'),
        ]);

        return back()->with('status', 'Portada actualizada');
    }

    /** Download the playlist QR as a PNG. */
    public function downloadQr(Playlist $playlist)
    {
        abort_unless($playlist->qr_path && Storage::disk('public')->exists($playlist->qr_path), 404);

        return Storage::disk('public')->download(
            $playlist->qr_path,
            "qr-{$playlist->slug}.png"
        );
    }

    /** Inline edit: playlist title. */
    public function updatePlaylist(Request $request, Playlist $playlist): JsonResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:120']]);
        $playlist->update($data);

        return response()->json(['ok' => true, 'title' => $playlist->title]);
    }

    /** Inline edit: a single track's title. */
    public function updateAudio(Request $request, Audio $audio): JsonResponse
    {
        $data = $request->validate(['title' => ['nullable', 'string', 'max:160']]);
        $audio->update(['title' => $data['title'] ?: null]);

        return response()->json(['ok' => true, 'title' => $audio->displayName()]);
    }

    /** Persist a new track order. Expects ordered array of audio IDs. */
    public function reorder(Request $request, Playlist $playlist): JsonResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $valid = $playlist->audios()->pluck('id')->all();
        foreach ($data['ids'] as $i => $id) {
            if (in_array($id, $valid, true)) {
                Audio::where('id', $id)->update(['order' => $i + 1]);
            }
        }

        return response()->json(['ok' => true]);
    }

    /** Delete a track (and its file). */
    public function destroyAudio(Audio $audio): JsonResponse
    {
        Storage::disk('public')->delete($audio->file_path);
        $audio->delete();

        return response()->json(['ok' => true]);
    }
}
