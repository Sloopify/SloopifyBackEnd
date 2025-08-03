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
        Schema::create('post_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignId('post_owner_id')->constrained('users')->onDelete('cascade');
            $table->enum('mute_type', ['24_hours', '7_days', '30_days', 'permanent']);
            $table->boolean('mute_reactions')->default(false);
            $table->boolean('mute_comments')->default(false);
            $table->boolean('mute_shares')->default(false);
            $table->boolean('mute_all')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Ensure one setting per post per owner
            $table->unique(['post_id', 'post_owner_id']);
            
            // Index for performance
            $table->index(['post_owner_id', 'mute_type']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_notification_settings');
    }
};
