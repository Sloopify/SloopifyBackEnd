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
        Schema::create('friend_notification_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');
            $table->enum('restriction_type', ['30_days', 'permanent']);
            $table->boolean('mute_reactions')->default(false);
            $table->boolean('mute_comments')->default(false);
            $table->boolean('mute_shares')->default(false);
            $table->boolean('mute_all')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'friend_id']);
            $table->index(['user_id', 'restriction_type'], 'fnr_user_restriction_idx');
            $table->index(['friend_id', 'restriction_type'], 'fnr_friend_restriction_idx');
            $table->index(['expires_at'], 'fnr_expires_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friend_notification_restrictions');
    }
};
