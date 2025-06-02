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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // For spatie/laravel-translatable
            $table->decimal('price', 8, 2); // Monthly price
            $table->json('features')->nullable(); // For spatie/laravel-translatable (list of features)
            $table->string('paypal_plan_id')->nullable()->unique(); // To store PayPal's plan ID
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
