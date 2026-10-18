<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('problem');
            $table->text('solution');
            // Palavras-chave e sinônimos usados pela equipe (ex.: "vpn, forticlient, acesso remoto").
            $table->string('keywords')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->boolean('is_synthetic')->default(false);
            $table->timestamps();

            // Busca por relevância (KnowledgeSearch). Em SQLite, usado nos testes, cai em LIKE.
            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->fullText(['title', 'problem', 'solution', 'keywords']);
            }
        });

        // Quais artigos foram reaproveitados em quais chamados:
        // base para medir recorrência e reuso de conhecimento.
        Schema::create('knowledge_article_ticket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['knowledge_article_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_ticket');
        Schema::dropIfExists('knowledge_articles');
    }
};
