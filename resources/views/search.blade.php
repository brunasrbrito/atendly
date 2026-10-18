<x-layouts.app title="Buscar soluções">
    <div class="search-hero">
        <h1>Como resolvemos isso antes?</h1>
        <p>Descreva o problema com as palavras do solicitante. A busca procura na base de conhecimento e nas soluções de chamados já encerrados.</p>

        <form method="GET">
            <div class="search-box search-box--lg">
                <x-icon name="search" />
                <input class="input" type="search" name="q" value="{{ $q }}" placeholder="Ex.: impressora offline, erro 539 na nota fiscal, vpn não conecta" aria-label="Descreva o problema" autofocus>
            </div>
            <div class="row" style="margin-top:.75rem">
                <select class="select select--sm" name="categoria" style="width:auto" aria-label="Categoria" onchange="this.form.submit()">
                    <option value="">Todas as categorias</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected($category === $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn--primary btn--sm">Buscar</button>
            </div>
        </form>

        @if ($q === '')
            <div class="suggest-chips">
                <span>Tente:</span>
                @foreach (['senha expirada', 'vpn não conecta', 'impressora offline', 'outlook não abre', 'rejeição nota fiscal'] as $example)
                    <a href="{{ route('search', ['q' => $example]) }}">{{ $example }}</a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($q !== '')
        <section class="card card--flush" style="max-width:960px">
            <div class="card__header">
                <h2>{{ $results->count() }} {{ $results->count() === 1 ? 'solução encontrada' : 'soluções encontradas' }}</h2>
                <span class="spacer"></span>
                @if ($terms)
                    <span class="small subtle">termos: {{ implode(', ', $terms) }}</span>
                @endif
            </div>
            <div class="card__body">
                @forelse ($results as $result)
                    @include('partials.search-result', ['result' => $result, 'terms' => $terms])
                @empty
                    <div class="empty">
                        <span class="empty__icon"><x-icon name="search" /></span>
                        <strong>Nenhuma solução encontrada</strong>
                        <p>Tente sinônimos (ex.: "senha" em vez de "login") ou menos palavras.</p>
                        <p class="small">Resolveu algo novo? <a href="{{ route('articles.create') }}">Registre um artigo</a> para a próxima pessoa encontrar.</p>
                    </div>
                @endforelse
            </div>
        </section>
    @endif
</x-layouts.app>
