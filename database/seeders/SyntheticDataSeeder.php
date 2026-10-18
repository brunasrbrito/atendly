<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Interview;
use App\Models\KnowledgeArticle;
use App\Models\Requester;
use App\Models\Ticket;
use App\Models\User;
use App\Support\SyntheticTicketGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Base de dados SINTÉTICA para testar o protótipo.
 *
 * Todos os registros recebem is_synthetic = true e são exibidos com selo na interface.
 * As distribuições de tempo (atraso maior no almoço, fora do expediente e no fim de semana)
 * são hipóteses do diagnóstico, não medições do suporte real.
 */
class SyntheticDataSeeder extends Seeder
{
    private const DAYS = 90;

    private SyntheticTicketGenerator $generator;

    /** @var Collection<int, User> */
    private Collection $technicians;

    /** @var Collection<int, Requester> */
    private Collection $requesters;

    /** @var Collection<string, Category> */
    private Collection $categories;

    /** @var array<string, KnowledgeArticle> */
    private array $articles = [];

    public function run(): void
    {
        mt_srand(2026); // reprodutível
        $this->generator = new SyntheticTicketGenerator;
        $catalog = SyntheticTicketGenerator::catalog();

        DB::transaction(function () use ($catalog) {
            $this->seedCategories($catalog['categories']);
            $this->seedTechnicians();
            $this->seedRequesters($catalog['departments']);
            $this->seedArticles($catalog['scenarios']);
            $this->seedTickets($catalog);
            $this->seedInterviews();
        });
    }

    private function seedCategories(array $categories): void
    {
        foreach ($categories as $data) {
            Category::updateOrCreate(['slug' => $data['slug']], $data);
        }
        $this->categories = Category::all()->keyBy('slug');
    }

    private function seedTechnicians(): void
    {
        foreach (['Alfa', 'Beta', 'Gama', 'Delta'] as $i => $name) {
            User::updateOrCreate(['email' => 'tecnico'.($i + 1).'@atendly.test'], [
                'name' => "Técnico {$name}",
                'password' => 'password',
                'role' => UserRole::Tecnico,
            ]);
        }
        $this->technicians = User::where('role', UserRole::Tecnico)->get();
    }

    private function seedRequesters(array $departments): void
    {
        foreach (range(1, 60) as $n) {
            $code = str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            Requester::updateOrCreate(['external_id' => "SOL-{$code}"], [
                'name' => "Solicitante {$code}",
                'department' => $departments[$n % count($departments)],
                'is_synthetic' => true,
            ]);
        }
        $this->requesters = Requester::where('is_synthetic', true)->get();
    }

    private function seedArticles(array $scenarios): void
    {
        foreach ($scenarios as $scenario) {
            if (! $scenario['article']) {
                continue;
            }
            $this->articles[$scenario['article_title']] = KnowledgeArticle::updateOrCreate(
                ['title' => $scenario['article_title']],
                [
                    'problem' => implode("\n", $scenario['descriptions']),
                    'solution' => $scenario['solution'],
                    'keywords' => $scenario['keywords'],
                    'category_id' => $this->categories[$scenario['category']]->id,
                    'author_id' => $this->technicians->random()->id,
                    'views' => mt_rand(5, 120),
                    'is_synthetic' => true,
                    'created_at' => now()->subDays(self::DAYS + mt_rand(10, 200)),
                ],
            );
        }
    }

    private function seedTickets(array $catalog): void
    {
        $now = now();

        for ($ago = self::DAYS; $ago >= 0; $ago--) {
            $day = $now->copy()->subDays($ago)->startOfDay();
            $count = $day->isWeekend() ? mt_rand(0, 2) : mt_rand(4, 9) + ($day->isMonday() ? 3 : 0);

            for ($i = 0; $i < $count; $i++) {
                $openedAt = $day->copy()->setTime($this->openingHour($day), mt_rand(0, 59), mt_rand(0, 59));
                if ($openedAt->greaterThan($now)) {
                    continue;
                }
                $this->createTicket($catalog, $openedAt, $now);
            }
        }
    }

    private function createTicket(array $catalog, Carbon $openedAt, Carbon $now): void
    {
        $scenario = $this->generator->pickScenario();
        $requester = $this->requesters->random();
        $payload = $this->generator->payload($scenario, $requester);
        $technician = $this->technicians->random();

        $ackAt = $openedAt->copy()->addMinutes($this->perceptionDelay($openedAt));
        $responseAt = $ackAt->copy()->addMinutes(mt_rand(1, 12));
        $workMinutes = (int) ($this->categories[$scenario['category']]->sla_resolution_minutes * mt_rand(10, 140) / 100);
        $resolvedAt = $responseAt->copy()->addMinutes($workMinutes);

        // Parte dos chamados dos últimos dias continua em andamento, para a fila não ficar vazia.
        if ($openedAt->diffInHours($now) < 120 && mt_rand(1, 100) <= 45) {
            $resolvedAt = $now->copy()->addDay();
        }

        // Define o estado atual de acordo com o que "já aconteceu" até agora.
        $status = match (true) {
            $ackAt->greaterThan($now) => TicketStatus::Novo,
            $resolvedAt->greaterThan($now) => mt_rand(1, 5) === 1 ? TicketStatus::Aguardando : TicketStatus::EmAtendimento,
            $resolvedAt->diffInHours($now) < 24 => TicketStatus::Resolvido,
            default => TicketStatus::Fechado,
        };

        $article = $scenario['article'] ? $this->articles[$scenario['article_title']] : null;
        $usedArticle = $article && ! $status->isOpen() && mt_rand(1, 100) <= 60;
        $isOpen = $status->isOpen();

        $ticket = Ticket::create([
            'external_id' => $payload['external_id'],
            'source' => 'integracao',
            'title' => $payload['title'],
            'description' => $payload['description'],
            'requester_id' => $requester->id,
            'category_id' => $this->categories[$scenario['category']]->id,
            'assigned_to' => $status === TicketStatus::Novo ? null : $technician->id,
            'status' => $status,
            'priority' => TicketPriority::from($scenario['priority']),
            'solution' => $isOpen ? null : $scenario['solution'],
            'opened_at' => $openedAt,
            'received_at' => $openedAt->copy()->addSeconds(mt_rand(5, 120)),
            'acknowledged_at' => $status === TicketStatus::Novo ? null : $ackAt,
            'first_response_at' => $status === TicketStatus::Novo || $responseAt->greaterThan($now) ? null : $responseAt,
            'resolved_at' => $isOpen ? null : $resolvedAt,
            'closed_at' => $status === TicketStatus::Fechado ? $resolvedAt->copy()->addHours(mt_rand(1, 24)) : null,
            'is_synthetic' => true,
            'created_at' => $openedAt,
            'updated_at' => $isOpen ? $now : $resolvedAt,
        ]);

        $this->createInteractions($ticket, $catalog, $technician, $usedArticle ? $article : null);

        if ($usedArticle) {
            $ticket->articles()->attach($article->id, [
                'user_id' => $technician->id,
                'created_at' => $resolvedAt,
                'updated_at' => $resolvedAt,
            ]);
        }
    }

    private function createInteractions(Ticket $ticket, array $catalog, User $technician, ?KnowledgeArticle $article): void
    {
        $rows = [[
            'author_type' => 'sistema', 'author_name' => 'Atendly', 'type' => 'evento', 'user_id' => null,
            'body' => 'Chamado recebido no Atendly (origem: integração).', 'created_at' => $ticket->received_at,
        ]];

        if (mt_rand(1, 3) === 1) {
            $rows[] = [
                'author_type' => 'solicitante', 'author_name' => $ticket->requester->name, 'type' => 'comentario', 'user_id' => null,
                'body' => $this->generator->pick($catalog['requester_messages']),
                'created_at' => $ticket->opened_at->copy()->addMinutes(mt_rand(1, 30)),
            ];
        }

        if ($ticket->acknowledged_at) {
            $rows[] = [
                'author_type' => 'sistema', 'author_name' => 'Atendly', 'type' => 'evento', 'user_id' => $technician->id,
                'body' => "{$technician->name} assumiu o chamado.", 'created_at' => $ticket->acknowledged_at,
            ];
        }

        if ($ticket->first_response_at) {
            $rows[] = [
                'author_type' => 'tecnico', 'author_name' => null, 'type' => 'comentario', 'user_id' => $technician->id,
                'body' => $this->generator->pick($catalog['technician_replies']), 'created_at' => $ticket->first_response_at,
            ];
        }

        if ($article) {
            $rows[] = [
                'author_type' => 'sistema', 'author_name' => 'Atendly', 'type' => 'evento', 'user_id' => $technician->id,
                'body' => "{$technician->name} aplicou o artigo \"{$article->title}\" da base de conhecimento.",
                'created_at' => $ticket->resolved_at,
            ];
        }

        if ($ticket->resolved_at) {
            $rows[] = [
                'author_type' => 'tecnico', 'author_name' => null, 'type' => 'comentario', 'user_id' => $technician->id,
                'body' => 'Chamado resolvido. Por favor, confirme se está tudo certo.', 'created_at' => $ticket->resolved_at,
            ];
        }

        foreach ($rows as $row) {
            $ticket->interactions()->create($row + ['updated_at' => $row['created_at']]);
        }
    }

    /** Distribuição de abertura concentrada no expediente, com alguns chamados fora de hora. */
    private function openingHour(Carbon $day): int
    {
        if ($day->isWeekend()) {
            return mt_rand(8, 20);
        }

        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 6 => mt_rand(6, 7),
            $roll <= 92 => mt_rand(8, 17),
            default => mt_rand(18, 22),
        };
    }

    /**
     * Hipótese a testar: chamados abertos no almoço, fora do expediente ou no fim de
     * semana demoram mais a ser percebidos, porque dependem de alguém olhar o sistema.
     */
    private function perceptionDelay(Carbon $openedAt): int
    {
        $base = (int) round(exp(mt_rand(100, 380) / 100)); // ~3 a ~45 min, cauda longa

        $hour = (int) $openedAt->format('G');
        $nextShift = $openedAt->copy()->addDay()->setTime(8, mt_rand(0, 40));

        if ($openedAt->isWeekend()) {
            $monday = $openedAt->copy()->next(Carbon::MONDAY)->setTime(8, mt_rand(0, 50));

            return (int) $openedAt->diffInMinutes($monday) + $base;
        }
        if ($hour >= 18) {
            return (int) $openedAt->diffInMinutes($nextShift->isWeekend() ? $nextShift->next(Carbon::MONDAY)->setTime(8, 10) : $nextShift) + $base;
        }
        if ($hour < 8) {
            return (int) $openedAt->diffInMinutes($openedAt->copy()->setTime(8, mt_rand(0, 30))) + $base;
        }
        if ($hour === 12 || $hour === 13) {
            return $base + mt_rand(25, 70);
        }

        return $base;
    }

    private function seedInterviews(): void
    {
        $notes = [
            ['Técnico N1', ['percepcao_atrasada', 'notificacao', 'ferramenta_terceiros'],
                'Relata que só percebe novos chamados quando abre a tela do sistema de terceiros; não há aviso sonoro. No horário de almoço a fila acumula.'],
            ['Técnico N2', ['recuperacao_conhecimento', 'whatsapp', 'dependencia_pessoas'],
                'Para problemas que já viu, procura no WhatsApp por palavra-chave. Quando não encontra, pergunta para quem atendeu antes; se a pessoa está de férias, refaz o diagnóstico do zero.'],
            ['Solicitante - área administrativa', ['comunicacao_solicitante', 'percepcao_atrasada'],
                'Abre o chamado e, sem retorno, manda mensagem direta para algum técnico. Não sabe se o chamado foi visto.'],
            ['Coordenação de TI', ['recorrencia', 'categorizacao', 'priorizacao'],
                'Acredita que impressoras e VPN são os problemas mais recorrentes, mas não tem números. A categorização varia entre técnicos.'],
        ];

        foreach ($notes as $i => [$profile, $themes, $text]) {
            Interview::updateOrCreate(['code' => 'E'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)], [
                'profile' => $profile,
                'conducted_on' => now()->subDays(20 - $i * 3)->toDateString(),
                'consent_recorded' => true,
                'themes' => $themes,
                'notes' => $text,
                'is_synthetic' => true,
            ]);
        }
    }
}
