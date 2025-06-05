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
        Schema::create('post_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('question');
            $table->json('options'); // array of poll options
            $table->boolean('multiple_choice')->default(false);
            $table->timestamp('ends_at')->nullable();
            $table->boolean('show_results_after_vote')->default(true);
            $table->boolean('show_results_after_end')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_polls');
    }
};
