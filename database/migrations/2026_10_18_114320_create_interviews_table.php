<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Entrevistas do diagnóstico. Não há campo de identificação:
        // o participante é referido só por um código (E01, E02...) e um perfil genérico.
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('profile'); // ex.: "Técnico N1", "Solicitante - área administrativa"
            $table->date('conducted_on');
            $table->boolean('consent_recorded')->default(false);
            $table->json('themes')->nullable();
            $table->text('notes');
            $table->boolean('is_synthetic')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
