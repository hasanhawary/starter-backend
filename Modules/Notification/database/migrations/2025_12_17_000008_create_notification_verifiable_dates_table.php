<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_verifiable_dates', static function (Blueprint $table) {
            $table->id();
            $table->string('module')->index()->comment('cause, contract, task, etc.');
            $table->string('model_type')->comment('App\\Models\\Cause, App\\Models\\Contract, etc.');
            $table->string('access_key')->comment('date, deadline, due_date, etc.');
            $table->string('type', 30)->default('date')->comment('date, datetime');
            $table->string('relation')->nullable()->comment('Relation name if date is from related model, null if direct column');
            $table->json('name')->nullable()->comment('{"en": "Cause Date", "ar": "تاريخ القضية"}');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['model_type', 'access_key'], 'verifiable_dates_model_access_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_verifiable_dates');
    }
};

