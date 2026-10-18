<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#5b6cff');
            // Metas de atendimento (em minutos) usadas para sinalizar atrasos.
            $table->unsignedInteger('sla_response_minutes')->default(30);
            $table->unsignedInteger('sla_resolution_minutes')->default(480);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
