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
            $table->integer('display_order')->default(0);
            $table->boolean('apply_to_download')->default(false);
            $table->boolean('auto_play')->default(false);
            $table->boolean('is_rotate')->default(false);
            $table->integer('rotate_angle')->default(0);
            $table->boolean('is_flip_horizontal')->default(false);
            $table->boolean('is_flip_vertical')->default(false);
            $table->json('metadata')->nullable(); // dimensions, duration, etc
            $table->string('filter_name')->nullable();
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
