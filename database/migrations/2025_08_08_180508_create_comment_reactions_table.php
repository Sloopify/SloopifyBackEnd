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
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comment_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('reaction_type', ['like', 'love', 'laugh', 'wow', 'sad', 'angry']);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('comment_id')->references('id')->on('post_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint to prevent duplicate reactions from same user
            $table->unique(['comment_id', 'user_id'], 'unique_comment_user_reaction');

            // Indexes for performance
            $table->index('comment_id', 'idx_comment_reactions_comment_id');
            $table->index('user_id', 'idx_comment_reactions_user_id');
            $table->index('reaction_type', 'idx_comment_reactions_type');
            $table->index(['comment_id', 'reaction_type'], 'idx_comment_reactions_comment_type');
            $table->index('created_at', 'idx_comment_reactions_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
};
