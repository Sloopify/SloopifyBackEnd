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
        Schema::create('post_interest_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('post_owner_id')->constrained('users')->onDelete('cascade');
            $table->enum('interest_type', ['interested', 'not_interested']);
            $table->timestamps();
            
            // Ensure one feedback per user per post
            $table->unique(['user_id', 'post_id']);
            
            // Indexes for efficient queries
            $table->index(['user_id', 'interest_type']);
            $table->index(['post_owner_id', 'interest_type']);
            $table->index(['post_id', 'interest_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_interest_feedback');
    }
};
