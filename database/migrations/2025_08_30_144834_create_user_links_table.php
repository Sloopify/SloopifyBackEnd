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
        Schema::create('user_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            
            // Link information
            $table->enum('link_type', [
                'website',
                'portfolio',
                'blog',
                'linkedin',
                'twitter',
                'facebook',
                'instagram',
                'youtube',
                'tiktok',
                'github',
                'behance',
                'dribbble',
                'pinterest',
                'snapchat',
                'telegram',
                'whatsapp',
                'other'
            ]);
            $table->string('link_url');
            $table->string('title')->nullable(); // Custom title for the link
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // For ordering links
            
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index(['user_id', 'link_type']);
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_links');
    }
};
