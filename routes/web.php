<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Upload surface  (audios.mysite.com)
|--------------------------------------------------------------------------
| In production, point the subdomain's document root / route group here.
| Locally these live at the app root so you can develop without DNS.
*/

// Dashboard (landing)
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::delete('/playlists/{playlist}/delete', [DashboardController::class, 'destroyPlaylist'])->name('playlist.destroy');

Route::controller(UploadController::class)->group(function () {
    Route::get('/create', 'create')->name('upload.create');
    Route::post('/upload', 'store')->name('upload.store');
    Route::get('/upload/{playlist}/success', 'success')->name('upload.success');
    Route::get('/upload/{playlist}/qr', 'downloadQr')->name('upload.qr');

    // Playlist editing
    Route::get('/playlists/{playlist}/edit', 'edit')->name('playlist.edit');
    Route::post('/playlists/{playlist}/cover', 'updateCover')->name('playlist.cover');

    // Inline editing (AJAX)
    Route::put('/playlists/{playlist}', 'updatePlaylist')->name('playlist.update');
    Route::put('/audios/{audio}', 'updateAudio')->name('audio.update');
    Route::post('/playlists/{playlist}/reorder', 'reorder')->name('playlist.reorder');
    Route::delete('/audios/{audio}', 'destroyAudio')->name('audio.destroy');
});

/*
|--------------------------------------------------------------------------
| Public player  (mysite.com/audio/{slug})
|--------------------------------------------------------------------------
| Tier 4 wires the player UI + stream endpoint. Stubbed here for routing.
*/
Route::controller(PlayerController::class)->group(function () {
    Route::get('/audio/{playlist}', 'show')->name('audio.show');
    Route::get('/audio/{playlist}/stream/{audio}', 'stream')->name('audio.stream');
});
