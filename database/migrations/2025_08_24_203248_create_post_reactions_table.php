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
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('reaction_id')->constrained('reactions')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure a user can only have one reaction per post
            $table->unique(['user_id', 'post_id']);
            
            // Indexes for better performance
            $table->index(['post_id', 'reaction_id']);
            $table->index(['user_id', 'reaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_reactions');
    }
};
