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
        Schema::create('user_educations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('education_level', [
                'high_school',
                'bachelors_degree',
                'masters_degree',
                'phd_doctorate',
                'vocational_training',
                'other_education'
            ]);
            $table->string('institution_name'); // School/University/Institution name
            $table->string('field_of_study')->nullable(); // Major/Field of Study/Specialization/Research Field/Certification
            $table->text('description')->nullable(); // For "Other Education" type
            $table->enum('status', [
                'currently_studying',
                'currently_enrolled',
                'graduated',
                'completed',
                'did_not_graduate',
                'dropped_out'
            ]);
            $table->year('start_year')->nullable();
            $table->year('end_year')->nullable();
            $table->boolean('is_current')->default(false); // To mark current education
            $table->integer('sort_order')->default(0); // For ordering education entries
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index(['user_id', 'education_level']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_educations');
    }
};
