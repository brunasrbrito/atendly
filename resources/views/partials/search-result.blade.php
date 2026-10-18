{{-- Um resultado de busca: artigo da base ou chamado resolvido. Espera $result, $terms e opcionalmente $ticket (para "Aplicar"). --}}
@php
    use App\Support\Highlighter;
    $model = $result['model'];
    $isArticle = $result['type'] === 'artigo';
    $url = $isArticle ? route('articles.show', $model) : route('tickets.show', $model);
    $compact = $compact ?? false;
@endphp
<article class="result">
    <div class="result__head">
        @if ($isArticle)
            <span class="badge badge--article"><x-icon name="book" style="width:12px;height:12px" /> Artigo</span>
        @else
            <span class="badge badge--outline mono">{{ $model->protocol() }}</span>
        @endif
        @unless ($compact)<x-category-tag :category="$model->category" />@endunless
        <x-synthetic-badge :when="$model->is_synthetic" />
        <span class="spacer"></span>
        @if ($result['occurrences'] > 1 || ($isArticle && $result['occurrences'] > 0))
            <span class="result__reuse" title="{{ $isArticle ? 'Chamados resolvidos com este artigo' : 'Chamados encontrados com a mesma solução' }}">
                <x-icon name="repeat" /> {{ $result['occurrences'] }}×
            </span>
        @endif
    </div>

    <a href="{{ $url }}" class="result__title">{{ Highlighter::mark($model->title, $terms) }}</a>

    @unless ($compact)
        <p class="result__snippet">{{ Highlighter::mark($isArticle ? $model->problem : $model->description, $terms, 110) }}</p>
    @endunless
    <div class="result__solution">{{ Highlighter::mark($model->solution, $terms, $compact ? 65 : 260) }}</div>

    <div class="result__foot">
        @if ($isArticle)
            @if ($model->keywords && ! $compact)<span>{{ $model->keywords }}</span>@endif
        @else
            <span>{{ $model->technician?->name ?? '—' }} · {{ ($model->resolved_at ?? $model->closed_at)?->format('d/m/Y') ?? '—' }}</span>
        @endif
        <span class="spacer"></span>
        @if (isset($ticket) && $isArticle && $ticket->status->isOpen())
            <form method="POST" action="{{ route('tickets.link-article', [$ticket, $model]) }}">
                @csrf
                <button class="btn btn--sm btn--primary">Aplicar</button>
            </form>
        @endif
    </div>
</article>
