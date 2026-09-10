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
        Schema::create('variable_assignments', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('variable_id')->constrained('variables')->onDelete('cascade');
            $table->morphs('variableable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variable_assignments');
    }
};

