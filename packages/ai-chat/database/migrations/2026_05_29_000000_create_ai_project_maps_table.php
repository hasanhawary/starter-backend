<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_project_maps', function (Blueprint $table) {
            $table->id();
            $table->string('scan_hash', 32)->unique();
            $table->json('project_map');
            $table->unsignedInteger('models_count')->default(0);
            $table->unsignedInteger('routes_count')->default(0);
            $table->unsignedInteger('controllers_count')->default(0);
            $table->unsignedInteger('services_count')->default(0);
            $table->unsignedInteger('policies_count')->default(0);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_project_maps');
    }
};
