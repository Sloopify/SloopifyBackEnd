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
        Schema::create('content_moderation_logs', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->json('detected_issues')->nullable(); // array of detected problems
            $table->decimal('toxicity_score', 3, 2)->nullable();
            $table->decimal('spam_score', 3, 2)->nullable();
            $table->json('flagged_words')->nullable();
            $table->enum('action_taken', ['approved', 'rejected', 'flagged_for_review']);
            $table->text('ai_reasoning')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_moderation_logs');
    }
};
