<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_receivers', static function (Blueprint $table) {
            $table->id();
            $table->string('module')->index()->comment('cause, contract, task, etc.');
            $table->string('type')->comment('role, relation, self');
            $table->string('relation')->nullable()->comment('Model relation name e.g. creator, users, assigner, department.users');
            $table->json('name')->nullable()->comment('{"en": "Assigned Users", "ar": "المستخدمين المعينين"}');
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_receivers');
    }
};

