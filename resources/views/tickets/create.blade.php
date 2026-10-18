<x-layouts.app title="Registrar chamado">
    <div class="page-header">
        <div class="page-header__titles">
            <a href="{{ route('tickets.index') }}" class="back-link"><x-icon name="arrow-left" /> Voltar à fila</a>
            <h1>{{ $related ? 'Novo chamado relacionado' : 'Registrar chamado' }}</h1>
            <p>{{ $related ? 'O chamado anterior está encerrado e não é reaberto. Descreva o que voltou a acontecer ou o novo problema.' : 'Use quando o chamado não chegar pela integração com o sistema de terceiros.' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="card form-card">
        @csrf
        <div class="card__body">
            @if ($related)
                <input type="hidden" name="related_ticket_id" value="{{ $related->id }}">
                <div class="alert alert--info">
                    <x-icon name="repeat" />
                    <span>Relacionado a <a href="{{ route('tickets.show', $related) }}" style="font-weight:700">{{ $related->protocol() }} · {{ $related->title }}</a>. Solicitante e categoria foram copiados.</span>
                </div>
            @endif
            <div class="grid grid--2">
                <div class="field">
                    <label for="requester_name">Solicitante</label>
                    <input class="input" id="requester_name" name="requester_name" value="{{ old('requester_name', $related?->requester?->name) }}" required>
                    <span class="field__hint">Use um identificador ou nome fictício nos testes.</span>
                    <x-field-error name="requester_name" />
                </div>
                <div class="field">
                    <label for="requester_department">Setor</label>
                    <input class="input" id="requester_department" name="requester_department" value="{{ old('requester_department', $related?->requester?->department) }}">
                </div>
            </div>

            <div class="field">
                <label for="title">Título</label>
                <input class="input" id="title" name="title" value="{{ old('title') }}" required maxlength="255">
                <x-field-error name="title" />
            </div>

            <div class="field">
                <label for="description">Descrição</label>
                <textarea class="textarea" id="description" name="description" required>{{ old('description') }}</textarea>
                <x-field-error name="description" />
            </div>

            <div class="grid grid--2">
                <div class="field">
                    <label for="category">Categoria</label>
                    <select class="select" id="category" name="category">
                        <option value="">Classificar depois</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected(old('category', $related?->category?->slug) === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="priority">Prioridade</label>
                    <select class="select" id="priority" name="priority">
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected(old('priority', $related?->priority->value ?? 'media') === $priority->value)>{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="external_id">Protocolo no sistema de origem</label>
                    <input class="input mono" id="external_id" name="external_id" value="{{ old('external_id') }}" placeholder="opcional">
                    <x-field-error name="external_id" />
                </div>
                <div class="field">
                    <label for="opened_at">Aberto em</label>
                    <input class="input" type="datetime-local" id="opened_at" name="opened_at" value="{{ old('opened_at') }}">
                    <span class="field__hint">Em branco = agora. Informe o horário original para medir o atraso de percepção.</span>
                    <x-field-error name="opened_at" />
                </div>
            </div>

            <div class="field">
                <label for="attachments">Imagens ou áudios</label>
                <input class="input" type="file" id="attachments" name="attachments[]" multiple accept="image/*,audio/*,.pdf,.txt,.log">
                <x-field-error name="attachments.0" />
            </div>

            <label class="check"><input type="checkbox" name="is_synthetic" value="1" @checked(old('is_synthetic', true))> Marcar como dado de teste</label>
        </div>
        <div class="card__footer">
            <a href="{{ route('tickets.index') }}" class="btn">Cancelar</a>
            <button class="btn btn--primary">Registrar</button>
        </div>
    </form>
</x-layouts.app>
