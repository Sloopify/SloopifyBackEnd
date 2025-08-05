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
        Schema::create('suggested_post_interest_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('post_owner_id')->constrained('users')->onDelete('cascade');
            $table->enum('interest_type', ['interested', 'not_interested']);
            $table->timestamps();
            
            $table->unique(['user_id', 'post_id']);
            $table->index(['user_id', 'interest_type'], 'spif_user_interest_idx');
            $table->index(['post_owner_id', 'interest_type'], 'spif_owner_interest_idx');
            $table->index(['post_id', 'interest_type'], 'spif_post_interest_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggested_post_interest_feedback');
    }
};
