<?php

use App\Enum\Subscription\PlanBillingCycleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->json('name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->string('cycle')->comment(PlanBillingCycleEnum::commentFormat()); // Using string to store enum value (monthly, yearly)
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('EGP');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->timestamps();

            // Unique constraint: one price per plan per cycle per currency
            $table->unique(['plan_id', 'cycle', 'currency']);
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('key');
            $table->string('value');
            $table->boolean('is_active')->default(true);
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plan_prices');
    }
};
