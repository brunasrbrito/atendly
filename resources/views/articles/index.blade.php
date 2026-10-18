@php use App\Support\Highlighter; @endphp
<x-layouts.app title="Base de conhecimento">
    <div class="page-header">
        <div class="page-header__titles">
            <h1>Base de conhecimento</h1>
            <p>Soluções documentadas pela equipe, ordenadas pelas mais reaproveitadas.</p>
        </div>
        <a href="{{ route('articles.create') }}" class="btn btn--primary"><x-icon name="plus" /> Novo artigo</a>
    </div>

    @if ($unpublished > 0)
        <div class="alert alert--warning">
            <x-icon name="lightbulb" style="width:18px" />
            <span><strong>{{ $unpublished }}</strong> chamados encerrados têm solução registrada mas não estão ligados a nenhum artigo. Esse conhecimento só é encontrado pela busca de chamados.</span>
        </div>
    @endif

    <form class="filters" method="GET">
        <div class="search-box" style="flex:1;max-width:420px">
            <x-icon name="search" />
            <input class="input" style="width:100%" type="search" name="q" value="{{ $q }}" placeholder="Filtrar artigos por palavra-chave">
        </div>
        @if ($q === '')
            <select class="select" name="categoria" onchange="this.form.submit()" aria-label="Categoria">
                <option value="">Todas as categorias</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(request('categoria') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        @endif
        <button class="btn">Filtrar</button>
    </form>

    <div class="card card--flush">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Artigo</th><th>Categoria</th><th>Usado em</th><th>Visualizações</th><th>Atualizado</th></tr></thead>
                <tbody>
                @forelse ($articles as $article)
                    <tr>
                        <td>
                            <a class="table__title" href="{{ route('articles.show', $article) }}">{{ Highlighter::mark($article->title, $terms) }}</a>
                            <div class="small muted">{{ $article->keywords }} <x-synthetic-badge :when="$article->is_synthetic" /></div>
                        </td>
                        <td>@if ($article->category)<x-category-tag :category="$article->category" />@endif</td>
                        <td>{{ $article->tickets_count }} chamado(s)</td>
                        <td>{{ $article->views }}</td>
                        <td class="small muted">{{ $article->updated_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty">Nenhum artigo encontrado.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
