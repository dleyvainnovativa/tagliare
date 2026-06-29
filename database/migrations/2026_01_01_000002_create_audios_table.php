<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');                 // storage path
            $table->string('original_name');             // as uploaded
            $table->string('title')->nullable();         // editable display name
            $table->unsignedInteger('duration')->default(0); // seconds, from getID3
            $table->unsignedInteger('order')->default(0);    // position in playlist
            $table->timestamps();

            $table->index(['playlist_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audios');
    }
};
