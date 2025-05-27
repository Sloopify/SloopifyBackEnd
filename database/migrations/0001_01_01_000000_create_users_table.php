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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_blocked')->default(false);
            $table->integer('age')->nullable();
            $table->date('birthday')->nullable();
            $table->string('phone');
            $table->string('img')->nullable();
            $table->string('bio')->nullable();
            $table->string('referral_code')->nullable();
            $table->string('referral_link')->nullable();
            $table->string('reffered_by')->nullable();
            $table->date('last_login_at')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->text('google_id')->nullable();
            $table->enum('provider',['google' , 'apple'])->nullable();
            $table->text('device_id')->nullable();
            $table->string('device_type')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
