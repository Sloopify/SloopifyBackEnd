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
        Schema::create('friend_muted_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('friend_id');
            $table->boolean('is_muted')->default(true);
            $table->timestamp('muted_at')->nullable();
            $table->string('reason')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('duration', ['1_month', 'always'])->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('friend_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'friend_id']);
            $table->index('is_muted');
            $table->index('muted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friend_muted_notifications');
    }
};
