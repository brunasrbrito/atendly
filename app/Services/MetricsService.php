<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores para o diagnóstico: atrasos de percepção, tempos de atendimento,
 * recorrência de problemas e reuso de conhecimento.
 *
 * Os cálculos são feitos em PHP para funcionar igual em MySQL e SQLite; o volume
 * esperado para o protótipo (alguns milhares de chamados) comporta essa abordagem.
 */
class MetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(int $days = 30, ?bool $synthetic = null): array
    {
        $from = now()->subDays($days)->startOfDay();

        /** @var Collection<int, Ticket> $tickets */
        $tickets = Ticket::query()
            ->with('category')
            ->where('opened_at', '>=', $from)
            ->when($synthetic !== null, fn ($q) => $q->where('is_synthetic', $synthetic))
            ->get();

        $perception = $tickets->map->perceptionMinutes()->filter(fn ($v) => $v !== null);
        $firstResponse = $tickets->filter->first_response_at
            ->map(fn (Ticket $t) => (int) $t->opened_at->diffInMinutes($t->first_response_at));
        $resolution = $tickets->map->resolutionMinutes()->filter(fn ($v) => $v !== null);

        $late = $tickets->filter(function (Ticket $t) {
            $limit = $t->category?->sla_response_minutes ?? config('atendly.ack_alert_minutes');

            return $t->waitingMinutes() > $limit;
        });

        $resolved = $tickets->filter(fn (Ticket $t) => ! $t->status->isOpen());
        $withArticle = $resolved->isEmpty() ? 0 : DB::table('knowledge_article_ticket')
            ->whereIn('ticket_id', $resolved->modelKeys())->distinct()->count('ticket_id');

        return [
            'days' => $days,
            'total' => $tickets->count(),
            'open' => $tickets->filter(fn (Ticket $t) => $t->status->isOpen())->count(),
            'unacknowledged' => Ticket::unacknowledged()->count(),
            'resolved' => $resolved->count(),
            'perception_avg' => $this->avg($perception),
            'perception_median' => $this->median($perception),
            'perception_p90' => $this->percentile($perception, 90),
            'first_response_avg' => $this->avg($firstResponse),
            'resolution_avg' => $this->avg($resolution),
            'resolution_median' => $this->median($resolution),
            'late_perception_rate' => $tickets->isEmpty() ? 0 : round($late->count() / $tickets->count() * 100, 1),
            'knowledge_reuse_rate' => $resolved->isEmpty() ? 0 : round($withArticle / $resolved->count() * 100, 1),
            'by_hour' => $this->perceptionByHour($tickets),
            'by_weekday' => $this->perceptionByWeekday($tickets),
            'by_day' => $this->volumeByDay($tickets, $days),
            'by_category' => $this->byCategory($tickets),
            'by_status' => collect(TicketStatus::cases())->mapWithKeys(fn (TicketStatus $s) => [
                $s->label() => $tickets->where('status', $s)->count(),
            ])->all(),
            'recurring' => $this->recurringProblems($from),
        ];
    }

    /**
     * Tempo até percepção (mediana) por hora de abertura — evidencia horários críticos.
     * A mediana evita que poucos chamados de fim de semana distorçam o gráfico.
     */
    private function perceptionByHour(Collection $tickets): array
    {
        $groups = $tickets->filter->acknowledged_at->groupBy(fn (Ticket $t) => (int) $t->opened_at->format('G'));

        return collect(range(0, 23))->mapWithKeys(fn (int $h) => [
            sprintf('%02dh', $h) => [
                'count' => $tickets->filter(fn (Ticket $t) => (int) $t->opened_at->format('G') === $h)->count(),
                'median' => $this->median(($groups[$h] ?? collect())->map->perceptionMinutes()),
            ],
        ])->all();
    }

    private function perceptionByWeekday(Collection $tickets): array
    {
        $names = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        $groups = $tickets->filter->acknowledged_at->groupBy(fn (Ticket $t) => $t->opened_at->dayOfWeek);

        return collect(range(0, 6))->mapWithKeys(fn (int $d) => [
            $names[$d] => $this->median(($groups[$d] ?? collect())->map->perceptionMinutes()),
        ])->all();
    }

    private function volumeByDay(Collection $tickets, int $days): array
    {
        $byDay = $tickets->groupBy(fn (Ticket $t) => $t->opened_at->toDateString());

        return collect(range($days - 1, 0))->mapWithKeys(function (int $ago) use ($byDay) {
            $date = now()->subDays($ago)->toDateString();

            return [$date => ($byDay[$date] ?? collect())->count()];
        })->all();
    }

    private function byCategory(Collection $tickets): array
    {
        return $tickets->groupBy(fn (Ticket $t) => $t->category?->name ?? 'Sem categoria')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'color' => $group->first()->category?->color ?? '#94a3b8',
                'count' => $group->count(),
                'perception_avg' => $this->avg($group->map->perceptionMinutes()->filter(fn ($v) => $v !== null)),
                'resolution_avg' => $this->avg($group->map->resolutionMinutes()->filter(fn ($v) => $v !== null)),
            ])
            ->sortByDesc('count')->values()->all();
    }

    /** Artigos mais reaplicados no período = problemas mais recorrentes. */
    private function recurringProblems($from): array
    {
        return KnowledgeArticle::query()
            ->with('category')
            ->withCount(['tickets as uses' => fn ($q) => $q->where('opened_at', '>=', $from)])
            ->orderByDesc('uses')
            ->limit(8)
            ->get()
            ->filter(fn ($a) => $a->uses > 0)
            ->map(fn (KnowledgeArticle $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'category' => $a->category?->name,
                'uses' => $a->uses,
            ])->values()->all();
    }

    private function avg(Collection $values): ?int
    {
        return $values->isEmpty() ? null : (int) round($values->avg());
    }

    private function median(Collection $values): ?int
    {
        return $values->isEmpty() ? null : (int) round($values->median());
    }

    private function percentile(Collection $values, int $p): ?int
    {
        if ($values->isEmpty()) {
            return null;
        }
        $sorted = $values->sort()->values();

        return (int) $sorted[(int) floor(($sorted->count() - 1) * $p / 100)];
    }
}
