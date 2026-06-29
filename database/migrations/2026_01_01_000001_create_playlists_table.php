<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();   // public URL key, long & random
            $table->string('title')->default('Audios');
            $table->string('cover_image')->nullable(); // storage path to cover
            $table->string('qr_path')->nullable();     // storage path to generated QR png
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
