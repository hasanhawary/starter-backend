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
        Schema::create('system_events', static function (Blueprint $table) {
            $table->id();
            $table->json('name')->nullable();
            $table->string('model_type');
            $table->string('module')->comment('Example Product, Finance, Checkout, etc.');
            $table->string('event_slug')->comment('Example: create_product, add_to_cart');
            $table->boolean('is_active')->default(true);
            $table->string('relation_type')->nullable();
            $table->string('relation')->nullable();
            $table->string('access_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_events');
    }
};
