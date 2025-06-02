<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_subscriptions')) {
            Schema::create('user_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
                $table->string('paypal_subscription_id')->unique()->nullable();
                $table->string('status');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->json('paypal_payload')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('user_subscriptions', 'paypal_subscription_id')) {
                    $table->string('paypal_subscription_id')->unique()->nullable()->after('subscription_plan_id');
                }
                if (!Schema::hasColumn('user_subscriptions', 'status')) {
                    $table->string('status')->default('pending')->after('paypal_subscription_id'); 
                }
                if (!Schema::hasColumn('user_subscriptions', 'starts_at')) {
                    $table->timestamp('starts_at')->nullable()->after('status');
                }
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
