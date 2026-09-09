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
        Schema::create('variables', static function (Blueprint $table) {
            $table->id();
            $table->mediumText('name')->nullable();
            $table->string('module')->nullable();
            $table->string('model_type');
            $table->string('access_key');
            $table->string('relation_type')->nullable();
            $table->string('enum_class')->nullable();
            $table->string('type')->default('column');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variables');
    }
};
