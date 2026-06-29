<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Playlist extends Model
{
    protected $fillable = ['slug', 'title', 'cover_image', 'qr_path'];

    public function audios(): HasMany
    {
        return $this->hasMany(Audio::class)->orderBy('order');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** True when the admin uploaded a custom cover. */
    public function hasCustomCover(): bool
    {
        return (bool) $this->cover_image;
    }

    /**
     * Cover to display. Returns the admin upload if present, otherwise the
     * site logo (transparent PNG — render it on a light background).
     */
    public function coverUrl(): string
    {
        return $this->cover_image
            ? Storage::url($this->cover_image)
            : asset('img/logo.png');
    }

    public function qrUrl(): ?string
    {
        return $this->qr_path ? Storage::url($this->qr_path) : null;
    }
}
