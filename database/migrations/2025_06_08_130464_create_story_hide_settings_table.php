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
        Schema::create('story_hide_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User who is hiding
            $table->foreignId('story_owner_id')->nullable()->constrained('users')->onDelete('cascade'); // Story owner to hide from
            $table->foreignId('specific_story_id')->nullable()->constrained('stories')->onDelete('cascade'); // Specific story to hide (null for all stories)
            $table->enum('hide_type', ['permanent', '30_days', 'specific_story']);
            $table->timestamp('expires_at')->nullable(); // For 30_days hide type
            $table->timestamps();

            $table->index(['user_id', 'story_owner_id']);
            $table->index(['user_id', 'hide_type']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_hide_settings');
    }
}; 