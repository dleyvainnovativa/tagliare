<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audio extends Model
{
    protected $table = 'audios';

    protected $fillable = [
        'playlist_id',
        'file_path',
        'original_name',
        'title',
        'duration',
        'order',
    ];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function displayName(): string
    {
        return $this->title ?: pathinfo($this->original_name, PATHINFO_FILENAME);
    }
}
