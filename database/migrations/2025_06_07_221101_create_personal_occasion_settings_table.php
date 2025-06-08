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
        Schema::create('personal_occasion_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('name', [
                'new_job', 'job_promotion', 'graduation', 'started_studies', 
                'relationship_status', 'moved_city', 'birthday', 'anniversary',
                'achievement', 'travel', 'other'
            ]);
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('web_icon')->nullable();
            $table->string('mobile_icon')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_occasion_settings');
    }
};
