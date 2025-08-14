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
            
            // Privacy settings
            $table->enum('privacy', ['public', 'friends', 'specific_friends', 'friend_except']);
            $table->json('specific_friends')->nullable(); // array of user IDs when privacy is specific_friends
            $table->json('friend_except')->nullable(); 
            
            // Text elements with positioning and styling
            $table->json('text_elements')->nullable(); // array of text elements with positioning, styling, and content
            
            // Background colors
            $table->json('background_color')->nullable(); // array of background colors
            
            // Elements with enhanced positioning (x, y, size_x, size_h, rotation, scale, theme)
            $table->json('mentions_elements')->nullable(); // [{friend_id, friend_name, x, y, theme, size_x, size_h, rotation, scale}, ...]
            $table->json('clock_element')->nullable(); // {clock, x, y, size_x, size_h, rotation, scale, theme}
            $table->json('feeling_element')->nullable(); // {feeling_id, feeling_name, x, y, size_x, size_h, rotation, scale, theme}
            $table->json('temperature_element')->nullable(); // {value, weather_code, isDay, x, y, theme, size_x, size_h, rotation, scale}
            $table->json('audio_element')->nullable(); // {audio_id, audio_name, audio_image, audio_url, x, y, size_x, size_h, rotation, scale, theme}
            $table->json('poll_element')->nullable(); // {question, poll_options[{option_id, option_name, votes}], x, y, size_x, size_h, rotation, scale, theme}
            $table->json('location_element')->nullable(); // {id, country_name, city_name, x, y, size_x, size_h, rotation, scale, theme}
            
            // New elements
            $table->json('drawing_elements')->nullable(); // array of drawing paths with points, stroke_width, stroke_color
            $table->json('gif_element')->nullable(); // {gif_url, x, y, size_x, size_h, rotation, scale}
            
            // Story-specific features
            $table->boolean('is_video_muted')->default(false);
            
            // Story expiry (24 hours by default)
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'deleted'])->default('active');
            
            $table->string('share_url')->nullable(); // Unique share URL for public story access
            
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