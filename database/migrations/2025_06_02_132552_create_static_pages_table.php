<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('static_pages')) {
            Schema::create('static_pages', function (Blueprint $table) {
                $table->id();
                $table->json('title'); 
                $table->string('slug')->unique(); 
                $table->json('content'); 
                $table->boolean('is_published')->default(false);
                $table->json('meta_keywords')->nullable(); 
                $table->json('meta_description')->nullable(); 
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('static_pages');
    }
};
