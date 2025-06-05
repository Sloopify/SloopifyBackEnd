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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('session_token')->unique();
            $table->string('device_type')->nullable(); // web, mobile, tablet
            $table->string('device_name')->nullable(); // iPhone 12, Chrome Browser, etc.
            $table->text('device_id')->nullable();
            $table->string('platform')->nullable(); // iOS, Android, Windows, macOS, etc.
            $table->string('browser')->nullable(); // Chrome, Safari, Firefox, etc.
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('location')->nullable(); // country, city from IP
            $table->timestamp('last_activity');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('push_token')->nullable()->after('user_agent');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_active']);
            $table->index('session_token');
            $table->index('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
