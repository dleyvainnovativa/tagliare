<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlayerController extends Controller
{
    /** Public player page. Full UI lands in Tier 4. */
    public function show(Playlist $playlist)
    {
        $playlist->load('audios');
        return view('player.show', compact('playlist'));
    }

    /**
     * Stream an audio file with a forced audio MIME + byte-range support.
     * This is the iOS/Android .m4a fix: serve as audio/mp4, never video.
     */
    public function stream(Playlist $playlist, Audio $audio)
    {
        abort_unless($audio->playlist_id === $playlist->id, 404);

        $path = Storage::disk('public')->path($audio->file_path);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type'  => 'audio/mp4',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
