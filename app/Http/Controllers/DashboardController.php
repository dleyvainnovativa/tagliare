<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Services\AudioUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected AudioUploadService $uploads) {}

    /** Dashboard: all playlists, newest first. Search is client-side. */
    public function index(): View
    {
        $playlists = Playlist::withCount('audios')
            ->latest()
            ->get();

        return view('dashboard.index', compact('playlists'));
    }

    /** Delete a playlist + its files. */
    public function destroyPlaylist(Playlist $playlist): JsonResponse
    {
        $this->uploads->deletePlaylist($playlist);

        return response()->json(['ok' => true]);
    }
}
