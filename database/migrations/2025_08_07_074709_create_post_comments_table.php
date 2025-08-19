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
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_comment_id')->nullable(); // For replies to comments
            $table->text('comment_text');
            $table->json('mentions')->nullable(); // Store mentioned users
            $table->json('media')->nullable(); // Store media files (images/videos)
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_comment_id')->references('id')->on('post_comments')->onDelete('cascade');

            // Indexes for performance
            $table->index('post_id', 'idx_post_comments_post_id');
            $table->index('user_id', 'idx_post_comments_user_id');
            $table->index('parent_comment_id', 'idx_post_comments_parent_id');
            $table->index(['post_id', 'parent_comment_id'], 'idx_post_comments_post_parent');
            $table->index('created_at', 'idx_post_comments_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};