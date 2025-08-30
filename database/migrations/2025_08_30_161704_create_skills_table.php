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
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // Technology & Digital, Creative & Arts, etc.
            $table->string('name'); // Skill name
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0); // For ordering within category
            $table->timestamps();

            // Indexes for better performance
            $table->index(['category', 'status']);
            $table->index(['status', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
