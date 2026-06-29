<?php

namespace App\Services;

use App\Models\Audio;
use App\Models\Playlist;
use getID3;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AudioUploadService
{
    /** Accepted audio extensions (WhatsApp exports + common). */
    public const ALLOWED_EXT = ['m4a', 'mp3', 'wav', 'opus', 'ogg', 'aac'];

    protected getID3 $id3;

    public function __construct()
    {
        $this->id3 = new getID3();
    }

    /**
     * Create a playlist from an initial batch of audio files.
     *
     * @param  UploadedFile[]  $files
     * @param  string[]  $names  Display names index-matched to $files (optional)
     */
    public function createPlaylist(array $files, ?string $title = null, ?UploadedFile $cover = null, array $names = []): Playlist
    {
        return DB::transaction(function () use ($files, $title, $cover, $names) {
            $playlist = Playlist::create([
                'slug'  => $this->uniqueSlug(),
                'title' => $title ?: 'Audios',
                'cover_image' => $cover ? $this->storeCover($cover) : null,
            ]);

            $this->addAudios($playlist, $files, $names);

            return $playlist->fresh('audios');
        });
    }

    /**
     * Append audio files to an existing playlist, continuing the order sequence.
     *
     * @param  UploadedFile[]  $files
     * @param  string[]  $names  Display names index-matched to $files (optional)
     */
    public function addAudios(Playlist $playlist, array $files, array $names = []): void
    {
        $start = (int) $playlist->audios()->max('order');
        $files = array_values($files);
        $names = array_values($names);

        foreach ($files as $i => $file) {
            $path = $file->store("audios/{$playlist->slug}", 'public');

            $name = trim($names[$i] ?? '');

            Audio::create([
                'playlist_id'   => $playlist->id,
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
                'title'         => $name !== '' ? $name : null, // null falls back to filename
                'duration'      => $this->extractDuration(Storage::disk('public')->path($path)),
                'order'         => $start + $i + 1,
            ]);
        }
    }

    /** Read duration (seconds) from file metadata. Pure PHP, no ffmpeg. */
    protected function extractDuration(string $absolutePath): int
    {
        try {
            $info = $this->id3->analyze($absolutePath);
            return (int) round($info['playtime_seconds'] ?? 0);
        } catch (\Throwable $e) {
            return 0; // never block an upload over metadata
        }
    }

    /**
     * Delete a playlist and all its on-disk assets (audio files, cover, QR).
     * DB rows for audios cascade via the FK; this handles the filesystem.
     */
    public function deletePlaylist(Playlist $playlist): void
    {
        $disk = Storage::disk('public');

        // Whole audio folder for this playlist
        $disk->deleteDirectory("audios/{$playlist->slug}");

        // Cover + QR live in shared folders, delete individually
        if ($playlist->cover_image) {
            $disk->delete($playlist->cover_image);
        }
        if ($playlist->qr_path) {
            $disk->delete($playlist->qr_path);
        }

        $playlist->delete(); // cascades audios rows
    }

    protected function storeCover(UploadedFile $cover): string
    {
        return $cover->store('covers', 'public');
    }

    protected function uniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(32));
        } while (Playlist::where('slug', $slug)->exists());

        return $slug;
    }
}
