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
        Schema::create('hidden_suggested_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('suggested_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('hide_type', ['permanent', '30_days']);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'suggested_user_id']);
            $table->index(['user_id', 'hide_type'], 'hsu_user_hide_idx');
            $table->index(['suggested_user_id', 'hide_type'], 'hsu_suggested_hide_idx');
            $table->index(['expires_at'], 'hsu_expires_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hidden_suggested_users');
    }
};
