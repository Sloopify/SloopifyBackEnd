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
        Schema::create('user_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            
            // Basic Information
            $table->string('job_title');
            $table->string('company_name');
            $table->string('location')->nullable();
            $table->enum('employment_type', [
                'full_time',
                'part_time',
                'internship',
                'freelance',
                'contract',
                'self_employed'
            ]);
            
            // Duration
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Null for "Currently Working"
            
            // Details
            $table->string('industry')->nullable();
            $table->text('job_description')->nullable();
            $table->text('responsibilities')->nullable();
            $table->json('skills_used')->nullable(); // Store as JSON array
            
            // Status
            $table->boolean('is_current_job')->default(false);
            $table->boolean('is_previous_job')->default(true);
            $table->integer('sort_order')->default(0); // For ordering job entries
            
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index(['user_id', 'employment_type']);
            $table->index(['user_id', 'is_current_job']);
            $table->index(['user_id', 'is_previous_job']);
            $table->index(['user_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_jobs');
    }
};
