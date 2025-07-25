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
        Schema::create('story_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['image', 'video']);
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('path');
            $table->string('url');
            $table->integer('order')->default(1); // Display order
            $table->decimal('rotate_angle', 8, 2)->default(0); // Rotation angle
            $table->decimal('scale', 8, 2)->default(1.0); // Scale factor
            $table->decimal('dx', 8, 2)->default(0); // X position offset
            $table->decimal('dy', 8, 2)->default(0); // Y position offset
            $table->json('metadata')->nullable(); // dimensions, duration, etc
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_media');
    }
}; 