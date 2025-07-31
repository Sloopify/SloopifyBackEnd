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
        Schema::create('story_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_owner_id')->constrained('users')->onDelete('cascade'); // Story owner
            $table->unsignedBigInteger('muted_user_id')->nullable(); // User who is muted (nullable for story-specific muting)
            $table->unsignedBigInteger('story_id')->nullable(); // Specific story (nullable for friend-specific muting)
            $table->boolean('mute_replies')->default(false);
            $table->boolean('mute_poll_votes')->default(false);
            $table->boolean('mute_all')->default(false); // Mute all notifications from this user
            $table->boolean('mute_story_notifications')->default(false); // Mute all notifications for this story
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('muted_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');

            // Unique constraint that allows multiple records for same user-friend with different stories
            $table->unique(['story_owner_id', 'muted_user_id', 'story_id'], 'story_notification_settings_unique');
            $table->index(['story_owner_id', 'mute_all']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_notification_settings');
    }
}; 