<?php

namespace App\Services;

use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Recupera soluções anteriores (artigos e chamados resolvidos) por palavras-chave.
 *
 * Em MySQL usa os índices FULLTEXT (busca por relevância, insensível a acentos com
 * collation utf8mb4_unicode_ci). Em outros bancos (ex.: SQLite nos testes) cai num
 * LIKE por termo, com a mesma interface.
 */
class KnowledgeSearch
{
    private const STOPWORDS = [
        'a', 'o', 'as', 'os', 'de', 'da', 'do', 'das', 'dos', 'e', 'em', 'no', 'na', 'nos', 'nas',
        'um', 'uma', 'uns', 'umas', 'para', 'pra', 'por', 'com', 'sem', 'que', 'nao', 'não', 'se',
        'meu', 'minha', 'esta', 'está', 'estou', 'tem', 'ter', 'foi', 'ser', 'mais', 'muito', 'como',
        'ao', 'aos', 'ou', 'eu', 'ele', 'ela', 'isso', 'esse', 'essa', 'este', 'bom', 'dia', 'tarde',
        'noite', 'favor', 'preciso', 'ajuda', 'consigo', 'quando', 'onde', 'todo', 'toda', 'mesmo',
    ];

    /** @return list<string> */
    public function terms(string $query): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', Str::lower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            fn (string $w) => mb_strlen($w) >= 3 && ! in_array($w, self::STOPWORDS, true),
        )));
    }

    /**
     * Busca unificada em artigos e chamados resolvidos, ordenada por relevância.
     *
     * @return Collection<int, array{type: string, model: KnowledgeArticle|Ticket, score: float}>
     */
    public function search(string $query, int $limit = 15, ?int $exceptTicketId = null, ?int $categoryId = null): Collection
    {
        $terms = $this->terms($query);
        if ($terms === []) {
            return collect();
        }

        $articles = $this->rank(
            KnowledgeArticle::query()->with('category')->withCount('tickets')
                ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId)),
            ['title', 'problem', 'solution', 'keywords'],
            $terms,
            $limit,
        )->map(fn ($a) => ['type' => 'artigo', 'model' => $a, 'score' => (float) $a->score * 1.25, 'occurrences' => $a->tickets_count]);

        // Chamados com a mesma solução viram um único resultado com o total de ocorrências:
        // evita repetição na lista e já mostra quanto o problema se repete.
        $tickets = $this->rank(
            Ticket::query()->with(['category', 'technician'])->whereNotNull('solution')
                ->when($exceptTicketId, fn ($q) => $q->whereKeyNot($exceptTicketId))
                ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId)),
            ['title', 'description', 'solution'],
            $terms,
            $limit * 6,
        )
            ->groupBy(fn (Ticket $t) => md5(Str::lower(trim($t->solution))))
            ->map(fn (Collection $group) => [
                'type' => 'chamado',
                'model' => $group->sortByDesc(fn (Ticket $t) => [$t->score, $t->opened_at])->first(),
                'score' => (float) $group->max('score'),
                'occurrences' => $group->count(),
            ])
            ->values();

        return $articles->concat($tickets)->sortByDesc('score')->take($limit)->values();
    }

    /**
     * Soluções parecidas com um chamado, usadas como sugestão na tela de atendimento.
     *
     * @return Collection<int, array{type: string, model: KnowledgeArticle|Ticket, score: float}>
     */
    public function similarTo(Ticket $ticket, int $limit = 5): Collection
    {
        // O título pesa mais que a descrição, por isso entra duas vezes.
        $query = $ticket->title.' '.$ticket->title.' '.Str::limit($ticket->description, 400, '');

        return $this->search($query, $limit, $ticket->id);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $columns
     * @param  list<string>  $terms
     * @return Collection<int, TModel>
     */
    private function rank(Builder $query, array $columns, array $terms, int $limit): Collection
    {
        $table = $query->getModel()->getTable();

        if (DB::connection()->getDriverName() === 'mysql') {
            $cols = implode(',', array_map(fn ($c) => "{$table}.{$c}", $columns));
            $against = implode(' ', $terms);

            if ($query->getQuery()->columns === null) {
                $query->select("{$table}.*");
            }

            return $query
                ->selectRaw("MATCH({$cols}) AGAINST(? IN NATURAL LANGUAGE MODE) AS score", [$against])
                ->whereRaw("MATCH({$cols}) AGAINST(? IN NATURAL LANGUAGE MODE)", [$against])
                ->orderByDesc('score')
                ->limit($limit)
                ->get();
        }

        $results = $query->where(function (Builder $q) use ($columns, $terms) {
            foreach ($terms as $term) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$term}%");
                }
            }
        })->limit($limit * 4)->get();

        foreach ($results as $model) {
            $haystack = Str::lower(implode(' ', array_map(fn ($c) => (string) $model->{$c}, $columns)));
            $model->score = collect($terms)->sum(fn ($t) => substr_count($haystack, $t));
        }

        return $results->sortByDesc('score')->take($limit)->values();
    }
}
