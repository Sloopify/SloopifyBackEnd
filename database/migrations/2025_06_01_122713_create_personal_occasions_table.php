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
        Schema::create('personal_occasions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->enum('occasion_type', [
                'new_job', 'job_promotion', 'graduation', 'started_studies', 
                'relationship_status', 'moved_city', 'birthday', 'anniversary',
                'achievement', 'travel', 'other'
            ]);
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('details')->nullable(); // company name, university, location, etc.
            $table->date('occasion_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_occasions');
    }
};
