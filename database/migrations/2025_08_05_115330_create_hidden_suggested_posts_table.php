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
        Schema::create('hidden_suggested_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('post_owner_id')->constrained('users')->onDelete('cascade');
            $table->enum('hide_type', ['permanent', '30_days']);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'post_id']);
            $table->index(['user_id', 'hide_type'], 'hsp_user_hide_idx');
            $table->index(['post_owner_id', 'hide_type'], 'hsp_owner_hide_idx');
            $table->index(['post_id', 'hide_type'], 'hsp_post_hide_idx');
            $table->index(['expires_at'], 'hsp_expires_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hidden_suggested_posts');
    }
};
