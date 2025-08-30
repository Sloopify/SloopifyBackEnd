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
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('skill_id');
            $table->integer('proficiency_level')->default(1); // 1-5 scale (Beginner to Expert)
            $table->text('description')->nullable(); // User's description of their skill level
            $table->boolean('is_public')->default(true); // Whether to show in public profile
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate user-skill combinations
            $table->unique(['user_id', 'skill_id']);
            
            // Indexes for better performance
            $table->index(['user_id', 'is_public']);
            $table->index(['skill_id', 'is_public']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_skills');
    }
};
