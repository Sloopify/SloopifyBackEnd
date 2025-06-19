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
        Schema::create('story_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who replied
            $table->text('reply_text')->nullable();
            $table->string('reply_media_path')->nullable(); // For media replies
            $table->enum('reply_type', ['text', 'media', 'emoji'])->default('text');
            $table->string('emoji')->nullable(); // For quick emoji reactions
            $table->timestamps();

            $table->index(['story_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_replies');
    }
}; 