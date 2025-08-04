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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['regular', 'poll', 'personal_occasion']);
            $table->text('content')->nullable();
            $table->json('text_properties')->nullable(); // font color, bold, italic, underline
            $table->json('background_color')->nullable(); // array of background colors
            $table->enum('privacy', ['public', 'friends', 'specific_friends', 'friend_except', 'only_me']);
            $table->json('specific_friends')->nullable(); // array of user IDs when privacy is specific_friends
            $table->json('friend_except')->nullable(); // array of user IDs to exclude when privacy is friend_except
            $table->boolean('disappears_24h')->default(false);
            $table->timestamp('disappears_at')->nullable();
            $table->json('mentions')->nullable(); // friends, places, feelings, activities
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('moderation_reason')->nullable();
            $table->string('gif_url')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('comments_enabled')->default(true);
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('is_saved')->default(false);
            $table->boolean('is_notified')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
