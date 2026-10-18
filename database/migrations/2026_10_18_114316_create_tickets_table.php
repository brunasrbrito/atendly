<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique();
            $table->string('source', 30)->default('manual'); // manual | webhook | simulador
            $table->string('title');
            $table->text('description');
            $table->foreignId('requester_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('novo')->index();
            $table->string('priority', 20)->default('media');
            $table->text('solution')->nullable();

            // Marcos de tempo do ciclo do chamado.
            $table->timestamp('opened_at')->index();          // abertura no sistema de origem
            $table->timestamp('received_at')->nullable();     // chegada no Atendly
            $table->timestamp('acknowledged_at')->nullable(); // técnico percebeu/assumiu
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->boolean('is_synthetic')->default(false)->index();
            $table->timestamps();

            // Busca por relevância (KnowledgeSearch). Em SQLite, usado nos testes, cai em LIKE.
            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->fullText(['title', 'description', 'solution']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
