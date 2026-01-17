<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Add plan_price_id to track which price was used for this subscription
            $table->foreignId('plan_price_id')->nullable()->after('plan_id')->constrained('plan_prices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['plan_price_id']);
            $table->dropColumn('plan_price_id');
        });
    }
};
