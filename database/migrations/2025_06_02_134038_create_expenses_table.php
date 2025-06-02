<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expenses')) { // Idempotency check
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->json('description'); // Translatable
                $table->decimal('amount', 10, 2); 
                $table->date('expense_date');
                $table->string('category')->nullable(); 
                $table->boolean('is_business_expense')->default(false);
                $table->string('receipt_path')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
