<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['image', 'video']);
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('path');
            $table->string('url');
            $table->json('metadata')->nullable(); // dimensions, duration, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};
