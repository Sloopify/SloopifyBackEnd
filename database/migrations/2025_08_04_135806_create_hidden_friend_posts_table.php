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
        Schema::create('hidden_friend_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');
            $table->enum('hide_type', ['permanent', '30_days']);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Ensure one hide setting per user per friend
            $table->unique(['user_id', 'friend_id']);
            
            // Indexes for efficient queries
            $table->index(['user_id', 'hide_type']);
            $table->index(['friend_id', 'hide_type']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hidden_friend_posts');
    }
};
