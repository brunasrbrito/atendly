<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requesters', function (Blueprint $table) {
            $table->id();
            // Identificador vindo do sistema de terceiros (pseudonimizado).
            $table->string('external_id')->nullable()->unique();
            $table->string('name');
            $table->string('department')->nullable();
            $table->boolean('is_synthetic')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requesters');
    }
};
