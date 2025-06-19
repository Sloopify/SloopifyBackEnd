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
            $table->foreignId('muted_user_id')->constrained('users')->onDelete('cascade'); // User who is muted
            $table->boolean('mute_replies')->default(false);
            $table->boolean('mute_poll_votes')->default(false);
            $table->boolean('mute_all')->default(false); // Mute all notifications from this user
            $table->timestamps();

            $table->unique(['story_owner_id', 'muted_user_id']);
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