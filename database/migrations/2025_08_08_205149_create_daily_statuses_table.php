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
        Schema::create('daily_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('web_icon')->nullable();
            $table->string('mobile_icon')->nullable();
            $table->boolean('status')->default(true); // true = active, false = inactive
            $table->timestamps();

            // Indexes for performance
            $table->index('status', 'idx_daily_statuses_status');
            $table->index('name', 'idx_daily_statuses_name');
        });

        // Add foreign key constraint and indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('daily_status_id')->references('id')->on('daily_statuses')->onDelete('set null');
            $table->index('daily_status_id', 'idx_users_daily_status_id');
            $table->index('daily_status_expires_at', 'idx_users_daily_status_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint and indexes from users table first
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['daily_status_id']);
            $table->dropIndex('idx_users_daily_status_id');
            $table->dropIndex('idx_users_daily_status_expires_at');
        });

        Schema::dropIfExists('daily_statuses');
    }
};
