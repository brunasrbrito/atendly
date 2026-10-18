<x-layouts.app :title="$article->title">
    <div class="page-header">
        <div class="page-header__titles">
            <a href="{{ route('articles.index') }}" class="back-link"><x-icon name="arrow-left" /> Base de conhecimento</a>
            <div class="row" style="margin:.35rem 0">
                @if ($article->category)<x-category-tag :category="$article->category" />@endif
                <x-synthetic-badge :when="$article->is_synthetic" />
            </div>
            <h1>{{ $article->title }}</h1>
            <p>
                Por {{ $article->author?->name ?? '—' }} · atualizado em {{ $article->updated_at->format('d/m/Y') }}
                · {{ $article->views }} visualizações · usado em {{ $article->tickets_count }} chamado(s)
            </p>
        </div>
        <div class="row">
            <a href="{{ route('articles.edit', $article) }}" class="btn">Editar</a>
            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('articles.destroy', $article) }}" data-confirm="Remover este artigo da base?">
                    @csrf @method('DELETE')
                    <button class="btn btn--danger">Remover</button>
                </form>
            @endif
        </div>
    </div>

    <div class="ticket-layout">
        <div class="stack">
            <section class="card">
                <div class="card__header"><h2>Problema / sintomas</h2></div>
                <div class="card__body prewrap">{{ $article->problem }}</div>
            </section>
            <section class="solution-box">
                <h3><x-icon name="check" style="width:16px;vertical-align:-3px" /> Solução</h3>
                <div class="prewrap">{{ $article->solution }}</div>
            </section>
        </div>
        <aside class="ticket-layout__side">
            @if ($article->keywords)
                <section class="card">
                    <div class="card__header"><h2>Palavras-chave</h2></div>
                    <div class="card__body chips">
                        @foreach ($article->keywordList() as $kw)
                            <a class="badge" href="{{ route('search', ['q' => $kw]) }}">{{ $kw }}</a>
                        @endforeach
                    </div>
                </section>
            @endif
            <section class="card card--flush">
                <div class="card__header"><h2>Chamados que usaram este artigo</h2></div>
                <div class="card__body">
                    <ul class="rank">
                        @forelse ($recentTickets as $t)
                            <li>
                                <a href="{{ route('tickets.show', $t) }}">{{ $t->protocol() }} · {{ $t->title }}</a>
                                <span class="small muted">{{ $t->opened_at->format('d/m') }}</span>
                            </li>
                        @empty
                            <li class="muted">Ainda não foi aplicado em nenhum chamado.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
            @if ($article->sourceTicket)
                <p class="small muted">Criado a partir do chamado <a href="{{ route('tickets.show', $article->sourceTicket) }}">{{ $article->sourceTicket->protocol() }}</a>.</p>
            @endif
        </aside>
    </div>
</x-layouts.app>
