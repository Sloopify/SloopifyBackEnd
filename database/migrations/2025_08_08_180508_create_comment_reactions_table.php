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
            $table->foreignId('comment_id')->constrained('post_comments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reaction_id')->constrained('reactions')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure a user can only have one reaction per comment
            $table->unique(['comment_id', 'user_id']);
            
            // Indexes for better performance
            $table->index(['comment_id', 'reaction_id']);
            $table->index(['user_id', 'reaction_id']);
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
