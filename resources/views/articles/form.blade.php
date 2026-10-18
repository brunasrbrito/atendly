<x-layouts.app :title="$article->exists ? 'Editar artigo' : 'Novo artigo'">
    <div class="page-header">
        <div class="page-header__titles">
            <a href="{{ $article->exists ? route('articles.show', $article) : route('articles.index') }}" class="back-link"><x-icon name="arrow-left" /> Voltar</a>
            <h1>{{ $article->exists ? 'Editar artigo' : 'Novo artigo' }}</h1>
            @if ($article->source_ticket_id && ! $article->exists)
                <p>Pré-preenchido a partir do chamado #{{ $article->source_ticket_id }}. Generalize o texto: retire dados do solicitante e deixe o passo a passo reaproveitável.</p>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ $article->exists ? route('articles.update', $article) : route('articles.store') }}" class="card form-card">
        @csrf
        @if ($article->exists) @method('PUT') @endif
        <input type="hidden" name="source_ticket_id" value="{{ old('source_ticket_id', $article->source_ticket_id) }}">
        <div class="card__body">
            <div class="field">
                <label for="title">Título</label>
                <input class="input" id="title" name="title" value="{{ old('title', $article->title) }}" required maxlength="255" placeholder="Ex.: VPN não conecta em home office">
                <x-field-error name="title" />
            </div>
            <div class="field">
                <label for="problem">Problema / sintomas</label>
                <textarea class="textarea" id="problem" name="problem" required placeholder="Como o problema aparece para o usuário? Mensagens de erro, contexto...">{{ old('problem', $article->problem) }}</textarea>
                <x-field-error name="problem" />
            </div>
            <div class="field">
                <label for="solution">Solução</label>
                <textarea class="textarea" id="solution" name="solution" required style="min-height:180px" placeholder="Passo a passo do que resolver.">{{ old('solution', $article->solution) }}</textarea>
                <x-field-error name="solution" />
            </div>
            <div class="grid grid--2">
                <div class="field">
                    <label for="keywords">Palavras-chave</label>
                    <input class="input" id="keywords" name="keywords" value="{{ old('keywords', $article->keywords) }}" placeholder="vpn, forticlient, acesso remoto">
                    <span class="field__hint">Separe por vírgula. Inclua os termos que as pessoas usam ao descrever o problema.</span>
                </div>
                <div class="field">
                    <label for="category_id">Categoria</label>
                    <select class="select" id="category_id" name="category_id">
                        <option value="">Sem categoria</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id', $article->category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label class="check"><input type="checkbox" name="is_synthetic" value="1" @checked(old('is_synthetic', $article->is_synthetic))> Conteúdo de teste</label>
        </div>
        <div class="card__footer">
            <button class="btn btn--primary">{{ $article->exists ? 'Salvar alterações' : 'Publicar artigo' }}</button>
        </div>
    </form>
</x-layouts.app>
