<?php

use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\SyntheticTicketGenerator;
use Illuminate\Support\Facades\Artisan;

/*
| Simula a chegada de chamados do sistema de terceiros (dados SINTÉTICOS).
| Útil para demonstrar o alerta de novos chamados na fila:
|   php artisan atendly:simular            -> 1 chamado
|   php artisan atendly:simular 5 --intervalo=20  -> 5 chamados, um a cada 20s
*/
Artisan::command('atendly:simular {quantidade=1} {--intervalo=0 : Segundos entre um chamado e outro}', function (TicketService $tickets) {
    $generator = new SyntheticTicketGenerator;
    $total = max(1, (int) $this->argument('quantidade'));

    for ($i = 1; $i <= $total; $i++) {
        $ticket = $tickets->receive($generator->payload(), 'integracao');
        $this->info("[{$i}/{$total}] {$ticket->protocol()} - {$ticket->title} ({$ticket->category?->name})");

        if ($i < $total && (int) $this->option('intervalo') > 0) {
            sleep((int) $this->option('intervalo'));
        }
    }
})->purpose('Simula a chegada de chamados sintéticos vindos do sistema de terceiros');

/*
| Carrega a base demo apenas se o banco ainda não tem chamados.
| Usado na instalação em produção: rodar o install de novo não duplica dados.
*/
Artisan::command('atendly:demo {--force : Carrega mesmo se já houver chamados}', function () {
    if (Ticket::query()->exists() && ! $this->option('force')) {
        $this->info('O banco já tem chamados: base demo mantida como está.');

        return;
    }

    $this->call('db:seed', ['--force' => true]);
})->purpose('Carrega a base de dados demo (somente em banco vazio)');
