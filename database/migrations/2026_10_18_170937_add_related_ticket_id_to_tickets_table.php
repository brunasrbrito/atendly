<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chamado encerrado não é reaberto: um novo chamado aponta para o anterior.
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('related_ticket_id')->nullable()->after('requester_id')
                ->constrained('tickets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('related_ticket_id');
        });
    }
};
