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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content')->nullable();
            
            // Text styling properties
            $table->json('text_properties')->nullable(); // color, font_type, bold, italic, underline, alignment
            $table->json('background_color')->nullable(); // array of background colors
            
            // Privacy settings (same as posts but without only_me)
            $table->enum('privacy', ['public', 'friends', 'specific_friends', 'friend_except']);
            $table->json('specific_friends')->nullable(); // array of user IDs when privacy is specific_friends
            $table->json('friend_except')->nullable(); 
            
            // Story-specific features
            $table->string('gif_url')->nullable();
            $table->boolean('is_video_muted')->default(false);
            
            // Elements with positioning (all optional)
            $table->json('location_element')->nullable(); // {id, x, y, features: []}
            $table->json('mentions_elements')->nullable(); // [{friend_id, x, y}, ...]
            $table->json('clock_element')->nullable(); // {x, y, features: []}
            $table->json('feeling_element')->nullable(); // {feeling_id, x, y, features: []}
            $table->json('temperature_element')->nullable(); // {x, y, features: [], value}
            $table->json('audio_element')->nullable(); // {audio_id, x, y, features: []}
            $table->json('poll_element')->nullable(); // {x, y, question, options: [], features: []}
            
            // Story expiry (24 hours by default)
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'deleted'])->default('active');
            
            $table->boolean('is_story_muted_notification')->default(false);

            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
}; 