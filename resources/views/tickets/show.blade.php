@php
    use App\Support\Highlighter;
    $locked = $ticket->isLocked();
    $pending = $ticket->acknowledged_at === null && ! $locked;
    $limit = $ticket->category?->sla_response_minutes ?? config('atendly.ack_alert_minutes');
    $gap = fn ($from, $to) => $from && $to ? Highlighter::duration((int) $from->diffInMinutes($to)) : null;
    $perception = $ticket->perceptionMinutes() ?? ($pending ? $ticket->waitingMinutes() : null);

    // Marcos do ciclo: mostram onde o tempo foi gasto.
    $steps = [
        ['label' => 'Aberto', 'at' => $ticket->opened_at, 'gap' => null],
        ['label' => 'Recebido', 'at' => $ticket->received_at, 'gap' => $gap($ticket->opened_at, $ticket->received_at)],
        ['label' => 'Percebido', 'at' => $ticket->acknowledged_at, 'gap' => $gap($ticket->opened_at, $ticket->acknowledged_at), 'late' => ($ticket->perceptionMinutes() ?? 0) > $limit],
        ['label' => '1ª resposta', 'at' => $ticket->first_response_at, 'gap' => $gap($ticket->acknowledged_at, $ticket->first_response_at)],
        ['label' => $ticket->status === \App\Enums\TicketStatus::Fechado ? 'Fechado' : 'Resolvido', 'at' => $ticket->resolved_at, 'gap' => $gap($ticket->opened_at, $ticket->resolved_at)],
    ];
    $current = $locked ? false : collect($steps)->search(fn ($s) => $s['at'] === null);
@endphp
<x-layouts.app :title="$ticket->protocol().' '.$ticket->title">
    <a href="{{ route('tickets.index') }}" class="back-link"><x-icon name="arrow-left" /> Fila de chamados</a>

    <header class="ticket-header">
        <div class="ticket-header__main">
            <div class="ticket-header__badges">
                <span class="mono subtle">{{ $ticket->protocol() }}</span>
                <x-status-badge :status="$ticket->status" />
                @if ($locked)
                    <span class="badge badge--outline"><x-icon name="lock" style="width:11px;height:11px" /> Somente leitura</span>
                @endif
                @if ($pending && $ticket->isPerceptionLate())
                    <span class="badge badge--late badge--dot">Acima do tempo de resposta</span>
                @endif
                <x-synthetic-badge :when="$ticket->is_synthetic" />
            </div>
            <h1>{{ $ticket->title }}</h1>
            <div class="ticket-header__meta">
                <span class="ticket-header__meta-item"><x-icon name="user" /> {{ $ticket->requester?->name ?? 'Solicitante não informado' }}</span>
                @if ($ticket->requester?->department)
                    <span class="ticket-header__meta-item"><x-icon name="building" /> {{ $ticket->requester->department }}</span>
                @endif
                <x-category-tag :category="$ticket->category" />
                <x-priority-badge :priority="$ticket->priority" />
                @if ($ticket->relatedTicket)
                    <a class="ticket-header__meta-item" href="{{ route('tickets.show', $ticket->relatedTicket) }}"><x-icon name="link" /> continuação de {{ $ticket->relatedTicket->protocol() }}</a>
                @endif
            </div>
        </div>
        <div class="page-header__actions">
            @if ($locked)
                <a href="{{ route('tickets.create', ['relacionado' => $ticket->id]) }}" class="btn btn--primary btn--lg"><x-icon name="plus" /> Abrir novo chamado relacionado</a>
            @elseif ($pending)
                <form method="POST" action="{{ route('tickets.acknowledge', $ticket) }}">
                    @csrf
                    <button class="btn btn--primary btn--lg"><x-icon name="check" /> Assumir chamado</button>
                </form>
            @else
                <button type="button" class="btn btn--primary btn--lg" data-open-dialog="#resolver"><x-icon name="check" /> Resolver chamado</button>
            @endif
        </div>
    </header>

    {{-- Andamento: onde o tempo foi gasto --}}
    <section class="card progress-card" aria-label="Andamento do chamado">
        <ol class="steps">
            @foreach ($steps as $i => $step)
                <li @class(['steps__item', 'is-done' => $step['at'] !== null, 'is-current' => $current === $i])>
                    <span class="steps__dot"></span>
                    <span class="steps__label">{{ $step['label'] }}</span>
                    <span class="steps__time">{{ $step['at']?->format('d/m H:i') ?? '—' }}</span>
                    @if ($current === $i && $i === 2)
                        <span class="steps__gap is-waiting">esperando <span class="waiting" data-waiting-since="{{ $ticket->opened_at->toIso8601String() }}" data-limit="{{ $limit }}">{{ Highlighter::duration($ticket->waitingMinutes()) }}</span></span>
                    @elseif ($step['gap'])
                        <span @class(['steps__gap', 'is-late' => $step['late'] ?? false])>+{{ $step['gap'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
        <div class="key-metrics">
            <div @class(['is-late' => $perception !== null && $perception > $limit])>
                <span>Até percepção</span>
                <strong>{{ Highlighter::duration($perception) }}</strong>
            </div>
            <div>
                <span>Até resolução</span>
                <strong>{{ Highlighter::duration($ticket->resolutionMinutes()) }}</strong>
            </div>
        </div>
    </section>

    <div class="ticket-layout">
        <div class="stack" style="gap:1.25rem">
            <section class="card">
                <div class="card__body">
                    <p class="section-title">Descrição do solicitante · {{ $ticket->opened_at->format('d/m/Y \à\s H:i') }}</p>
                    <div class="ticket-description prewrap">{{ $ticket->description }}</div>
                    @include('partials.attachments', ['attachments' => $ticket->attachments->whereNull('ticket_interaction_id')])
                </div>
            </section>

            @if ($ticket->solution)
                <section class="solution-box">
                    <h2><x-icon name="check" /> {{ $locked ? 'Solução aplicada' : 'Solução em rascunho' }}</h2>
                    <div class="prewrap">{{ $ticket->solution }}</div>
                    @if ($locked)
                        <div class="solution-box__footer">
                            @if ($ticket->articles->isNotEmpty())
                                <x-icon name="book-check" style="width:16px;color:var(--success)" />
                                <span class="small muted">Baseada em: @foreach ($ticket->articles as $a)<a href="{{ route('articles.show', $a) }}">{{ $a->title }}</a>@if (! $loop->last), @endif @endforeach</span>
                            @else
                                <a class="btn btn--sm" href="{{ route('articles.create', ['chamado' => $ticket->id]) }}"><x-icon name="book" /> Transformar em artigo</a>
                                <span class="small muted">Assim a próxima pessoa encontra essa solução na busca.</span>
                            @endif
                        </div>
                    @endif
                </section>
            @endif

            <section class="card" id="historico">
                <div class="card__header">
                    <x-icon name="repeat" />
                    <h2>Histórico</h2>
                    <span class="small subtle">{{ $ticket->interactions->count() }} {{ $ticket->interactions->count() === 1 ? 'registro' : 'registros' }}</span>
                </div>
                <div class="card__body">
                    <ol class="timeline">
                        @foreach ($ticket->interactions as $interaction)
                            @php($kind = $interaction->type === 'evento' ? 'evento' : ($interaction->author_type === 'solicitante' ? 'solicitante' : $interaction->type))
                            <li class="timeline__item timeline__item--{{ $kind }}">
                                @if ($kind === 'evento')
                                    <span class="timeline__avatar"><x-icon name="clock" /></span>
                                @else
                                    <x-avatar :name="$interaction->authorLabel()" class="timeline__avatar" />
                                @endif
                                <div class="timeline__bubble">
                                    <div class="timeline__meta">
                                        @if ($kind !== 'evento')<strong>{{ $interaction->authorLabel() }}</strong>@endif
                                        @if ($kind === 'solicitante')<span class="badge badge--outline">Solicitante</span>@endif
                                        @if ($interaction->type === 'nota_interna')<span class="badge badge--outline">Nota interna</span>@endif
                                        <time datetime="{{ $interaction->created_at->toIso8601String() }}" title="{{ $interaction->created_at->format('d/m/Y H:i') }}">{{ $interaction->created_at->format('d/m H:i') }}</time>
                                    </div>
                                    <div class="prewrap">{{ $interaction->body }}</div>
                                    @include('partials.attachments', ['attachments' => $interaction->attachments])
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                @if ($locked)
                    <div class="locked-note">
                        <x-icon name="lock" />
                        <span>Chamado encerrado em {{ ($ticket->closed_at ?? $ticket->resolved_at)?->format('d/m/Y \à\s H:i') }}. Para um novo contato, <a href="{{ route('tickets.create', ['relacionado' => $ticket->id]) }}">abra um chamado relacionado</a>.</span>
                    </div>
                @else
                    <form class="composer" method="POST" action="{{ route('tickets.interactions.store', $ticket) }}" enctype="multipart/form-data">
                        @csrf
                        <label for="body" class="sr-only">Nova interação</label>
                        <textarea class="textarea" id="body" name="body" placeholder="Responda ao solicitante ou registre uma nota interna…">{{ old('body') }}</textarea>
                        <x-field-error name="body" />
                        <x-field-error name="attachments.0" />
                        <div class="composer__tools">
                            <select class="select select--sm" name="type" style="width:auto" aria-label="Tipo de interação">
                                <option value="comentario">Resposta ao solicitante</option>
                                <option value="nota_interna" @selected(old('type') === 'nota_interna')>Nota interna</option>
                            </select>
                            <label class="btn btn--sm btn--ghost" for="attachments" title="Anexar imagem, áudio ou arquivo"><x-icon name="paperclip" /> Anexar</label>
                            <input class="sr-only" type="file" id="attachments" name="attachments[]" multiple accept="image/*,audio/*,.pdf,.txt,.log" data-file-list="#composer-files">
                            <button type="button" class="btn btn--sm btn--ghost" data-record-audio="#attachments"><x-icon name="mic" /> <span data-record-label>Gravar áudio</span></button>
                            <span class="composer__files" id="composer-files"></span>
                            <span class="spacer"></span>
                            <button class="btn btn--primary">Enviar</button>
                        </div>
                    </form>
                @endif
            </section>
        </div>

        <aside class="ticket-layout__side">
            @if ($locked)
                <section class="card">
                    <div class="card__header"><x-icon name="clipboard" /><h2>Detalhes</h2></div>
                    <div class="card__body">
                        <dl class="details-list">
                            <dt>Status</dt><dd><x-status-badge :status="$ticket->status" /></dd>
                            <dt>Categoria</dt><dd><x-category-tag :category="$ticket->category" /></dd>
                            <dt>Prioridade</dt><dd><x-priority-badge :priority="$ticket->priority" /></dd>
                            <dt>Técnico</dt><dd>@if ($ticket->technician)<x-avatar :name="$ticket->technician->name" size="sm" /> {{ $ticket->technician->name }}@else — @endif</dd>
                            <dt>Origem</dt><dd>{{ \App\Models\Ticket::sourceLabel($ticket->source) }}</dd>
                            <dt>Meta de resposta</dt><dd>{{ $limit }} min</dd>
                        </dl>
                    </div>
                </section>

                @if ($ticket->followUps->isNotEmpty())
                    <section class="card card--flush">
                        <div class="card__header"><x-icon name="link" /><h2>Chamados relacionados</h2></div>
                        <div class="card__body">
                            <ul class="rank rank--simple">
                                @foreach ($ticket->followUps as $follow)
                                    <li>
                                        <a href="{{ route('tickets.show', $follow) }}" class="truncate">{{ $follow->protocol() }} · {{ $follow->title }}</a>
                                        <x-status-badge :status="$follow->status" />
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                @endif
            @else
                <section class="card">
                    <div class="card__header"><x-icon name="clipboard" /><h2>Detalhes</h2></div>
                    <form class="card__body" method="POST" action="{{ route('tickets.update', $ticket) }}">
                        @csrf
                        @method('PATCH')
                        <div class="details-grid">
                            <div class="field">
                                <label for="status">Situação</label>
                                <select class="select select--sm" id="status" name="status">
                                    @foreach ($openStatuses as $status)
                                        <option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="priority">Prioridade</label>
                                <select class="select select--sm" id="priority" name="priority">
                                    @foreach ($priorities as $priority)
                                        <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="category_id">Categoria</label>
                                <select class="select select--sm" id="category_id" name="category_id">
                                    <option value="">Sem categoria</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($ticket->category_id === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="assigned_to">Técnico</label>
                                <select class="select select--sm" id="assigned_to" name="assigned_to">
                                    <option value="">Ninguém</option>
                                    @foreach ($technicians as $tech)
                                        <option value="{{ $tech->id }}" @selected($ticket->assigned_to === $tech->id)>{{ $tech->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button class="btn btn--block">Salvar detalhes</button>
                    </form>
                </section>

                <section class="card suggestions-card">
                    <div class="card__header">
                        <x-icon name="lightbulb" />
                        <h2>Soluções semelhantes</h2>
                    </div>
                    @forelse ($similar as $result)
                        @php($model = $result['model'])
                        @php($isArticle = $result['type'] === 'artigo')
                        <details class="suggestion" @if ($loop->first) open @endif>
                            <summary>
                                <span class="suggestion__kind">
                                    @if ($isArticle)<x-icon name="book" /> Artigo da base @else Chamado {{ $model->protocol() }} @endif
                                    @if ($result['occurrences'] > 1) · <x-icon name="repeat" /> {{ $result['occurrences'] }}× @endif
                                </span>
                                <span class="suggestion__title">{{ Highlighter::mark($model->title, $terms) }}</span>
                                <x-icon name="chevron-down" class="suggestion__chevron" />
                            </summary>
                            <div class="suggestion__body">
                                <div class="result__solution">{{ Highlighter::mark($model->solution, $terms, 130) }}</div>
                                <div class="suggestion__actions">
                                    <a href="{{ $isArticle ? route('articles.show', $model) : route('tickets.show', $model) }}">Ver completo</a>
                                    <span class="spacer"></span>
                                    @if ($isArticle)
                                        <form method="POST" action="{{ route('tickets.link-article', [$ticket, $model]) }}">
                                            @csrf
                                            <button class="btn btn--sm btn--primary">Usar esta solução</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="empty small">
                            <span class="empty__icon"><x-icon name="search" /></span>
                            <p>Nenhuma solução anterior parecida.</p>
                        </div>
                    @endforelse
                    <div class="card__footer" style="justify-content:flex-start">
                        <a class="small" style="font-weight:600" href="{{ route('search', ['q' => $ticket->title]) }}">Buscar com outras palavras <x-icon name="arrow-right" style="width:13px;vertical-align:-2px" /></a>
                    </div>
                </section>
            @endif
        </aside>
    </div>

    @unless ($locked)
        {{-- Resolver: status final + solução (obrigatória) --}}
        <dialog class="dialog" id="resolver" aria-labelledby="resolver-title" @if ($errors->has('solution')) data-open-on-load @endif>
            <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                @csrf
                <div class="dialog__header">
                    <div class="spacer">
                        <h2 id="resolver-title">Resolver chamado</h2>
                        <p>Depois de encerrado, o chamado não pode mais ser alterado.</p>
                    </div>
                    <button type="button" class="btn btn--ghost btn--icon btn--sm" data-close-dialog aria-label="Fechar"><x-icon name="x" /></button>
                </div>
                <div class="dialog__body">
                    <div class="choice-cards" role="radiogroup" aria-label="Status final">
                        <label>
                            <input type="radio" name="status" value="resolvido" @checked(old('status', 'resolvido') === 'resolvido')>
                            <div><strong>Resolvido</strong><span>Aguarda o solicitante confirmar.</span></div>
                        </label>
                        <label>
                            <input type="radio" name="status" value="fechado" @checked(old('status') === 'fechado')>
                            <div><strong>Fechado</strong><span>Solução confirmada, encerra de vez.</span></div>
                        </label>
                    </div>
                    <div class="field">
                        <label for="solution">O que resolveu?</label>
                        <textarea class="textarea" id="solution" name="solution" required style="min-height:150px" placeholder="Descreva o passo a passo. Esse texto alimenta a busca de soluções.">{{ old('solution', $ticket->solution) }}</textarea>
                        <x-field-error name="solution" />
                        @if ($similar->where('type', 'artigo')->isNotEmpty() && blank($ticket->solution))
                            <span class="field__hint">Dica: em "Soluções semelhantes", "Usar esta solução" preenche este campo.</span>
                        @endif
                    </div>
                </div>
                <div class="dialog__footer">
                    <button type="button" class="btn" data-close-dialog>Cancelar</button>
                    <button class="btn btn--primary"><x-icon name="check" /> Encerrar chamado</button>
                </div>
            </form>
        </dialog>
    @endunless
</x-layouts.app>
