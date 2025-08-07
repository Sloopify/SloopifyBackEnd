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
        Schema::create('hidden_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('comment_id');
            $table->unsignedBigInteger('comment_owner_id');
            $table->unsignedBigInteger('post_id');
            $table->enum('hide_type', ['permanent', '30_days'])->default('30_days');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('comment_id')->references('id')->on('post_comments')->onDelete('cascade');
            $table->foreign('comment_owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');

            // Unique constraint to prevent duplicate hides
            $table->unique(['user_id', 'comment_id'], 'unique_user_comment_hide');

            // Indexes for performance
            $table->index('user_id', 'idx_hidden_comments_user_id');
            $table->index('comment_id', 'idx_hidden_comments_comment_id');
            $table->index('comment_owner_id', 'idx_hidden_comments_owner_id');
            $table->index('post_id', 'idx_hidden_comments_post_id');
            $table->index('hide_type', 'idx_hidden_comments_hide_type');
            $table->index('expires_at', 'idx_hidden_comments_expires_at');
            $table->index(['user_id', 'expires_at'], 'idx_hidden_comments_user_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hidden_comments');
    }
};
