@php use App\Support\Highlighter; @endphp
<x-layouts.app title="Fila de chamados">
    <div class="page-header">
        <div class="page-header__titles">
            <h1>Fila de chamados</h1>
            <p>Quem está esperando aparece primeiro. Novos chamados chegam automaticamente.</p>
        </div>
        <div class="page-header__actions">
            <a href="{{ route('tickets.create') }}" class="btn"><x-icon name="plus" /> Registrar chamado</a>
        </div>
    </div>

    @if ($pending['count'] > 0)
        <section class="pending-strip" aria-live="polite">
            <span class="pending-strip__icon"><x-icon name="alarm" /></span>
            <div class="pending-strip__text">
                <strong>{{ $pending['count'] }} {{ $pending['count'] === 1 ? 'chamado aguardando' : 'chamados aguardando' }} um técnico</strong>
                <span>
                    O mais antigo espera há {{ Highlighter::duration($pending['oldest']) }}
                    @if ($pending['late'] > 0) · <strong style="display:inline;font-size:inherit;color:var(--danger)">{{ $pending['late'] }} acima do tempo esperado</strong>@endif
                </span>
            </div>
            <form method="POST" action="{{ route('tickets.acknowledge-next') }}">
                @csrf
                <button class="btn btn--attention btn--lg"><x-icon name="zap" /> Assumir o próximo</button>
            </form>
        </section>
    @else
        <section class="pending-strip pending-strip--calm">
            <span class="pending-strip__icon"><x-icon name="check" /></span>
            <div class="pending-strip__text">
                <strong>Fila em dia</strong>
                <span>Nenhum chamado esperando. Você será avisado quando chegar um novo.</span>
            </div>
        </section>
    @endif

    <div class="alert alert--info" data-refresh-notice hidden>
        <x-icon name="bell" />
        <span data-refresh-text></span>
        <a href="{{ request()->fullUrl() }}" style="margin-left:auto;font-weight:700">Atualizar a fila</a>
    </div>

    <div class="row" style="justify-content:space-between;align-items:flex-start">
        <nav class="segmented" aria-label="Visões da fila">
            @foreach ($views as $key => $label)
                <a href="{{ route('tickets.index', ['ver' => $key]) }}" @class(['segmented__item', 'is-active' => $view === $key]) @if ($view === $key) aria-current="page" @endif>
                    {{ $label }}
                    @isset($counts[$key])
                        <span @class(['segmented__count', 'is-alert' => $key === 'pendentes' && $counts[$key] > 0])>{{ $counts[$key] }}</span>
                    @endisset
                </a>
            @endforeach
        </nav>

        <form class="filters" method="GET" style="margin-bottom:1rem">
            <input type="hidden" name="ver" value="{{ $view }}">
            <div class="search-box">
                <x-icon name="search" />
                <input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Título ou protocolo" aria-label="Filtrar por título ou protocolo">
            </div>
            <select class="select" name="categoria" onchange="this.form.submit()" aria-label="Categoria">
                <option value="">Todas as categorias</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('categoria') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select class="select" name="prioridade" onchange="this.form.submit()" aria-label="Prioridade" style="min-width:150px">
                <option value="">Qualquer prioridade</option>
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->value }}" @selected(request('prioridade') === $priority->value)>{{ $priority->label() }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="card card--flush">
        <ul class="queue">
            @forelse ($tickets as $ticket)
                @php
                    $isPending = $ticket->acknowledged_at === null && $ticket->status->isOpen();
                    $limit = $ticket->category?->sla_response_minutes ?? config('atendly.ack_alert_minutes');
                    $closedView = in_array($view, ['encerrados', 'todos']);
                @endphp
                <li @class(['queue__item', 'is-pending' => $isPending, 'is-late' => $isPending && $ticket->isPerceptionLate()])>
                    <div class="queue__main">
                        <a href="{{ route('tickets.show', $ticket) }}" class="queue__title">{{ $ticket->title }}</a>
                        <div class="queue__meta">
                            <span class="queue__status-inline"><x-status-badge :status="$ticket->status" /></span>
                            <span class="mono">{{ $ticket->protocol() }}</span>
                            <span>{{ $ticket->requester?->name ?? 'Solicitante não informado' }}@if ($ticket->requester?->department) · {{ $ticket->requester->department }}@endif</span>
                            <x-category-tag :category="$ticket->category" />
                            <x-synthetic-badge :when="$ticket->is_synthetic" />
                        </div>
                    </div>

                    <div class="queue__col"><x-status-badge :status="$ticket->status" /></div>
                    <div class="queue__col"><x-priority-badge :priority="$ticket->priority" /></div>
                    <div class="queue__col">
                        @if ($ticket->technician)
                            <x-avatar :name="$ticket->technician->name" size="sm" />
                            <span class="truncate">{{ $ticket->technician->name }}</span>
                        @else
                            <span class="subtle">Sem técnico</span>
                        @endif
                    </div>

                    <div class="queue__side">
                        @if ($closedView || ! $isPending)
                            <div class="queue__wait">
                                <span>{{ $closedView ? 'aberto em' : 'aberto' }}</span>
                                <span class="muted num" style="font-size:12.5px;font-weight:600" title="{{ $ticket->opened_at->format('d/m/Y H:i') }}">
                                    {{ $closedView ? $ticket->opened_at->format('d/m H:i') : $ticket->opened_at->diffForHumans(short: true) }}
                                </span>
                            </div>
                        @else
                            <div class="queue__wait">
                                <span>aguardando</span>
                                <strong class="waiting" data-waiting-since="{{ $ticket->opened_at->toIso8601String() }}" data-limit="{{ $limit }}">{{ Highlighter::duration($ticket->waitingMinutes()) }}</strong>
                            </div>
                            <form method="POST" action="{{ route('tickets.acknowledge', $ticket) }}">
                                @csrf
                                <button class="btn btn--primary btn--sm">Assumir</button>
                            </form>
                        @endif
                    </div>
                </li>
            @empty
                <li>
                    <div class="empty">
                        <span class="empty__icon"><x-icon name="inbox" /></span>
                        <strong>Nada por aqui</strong>
                        <p>Nenhum chamado nesta visão{{ request()->hasAny(['q', 'categoria', 'prioridade']) ? ' com os filtros atuais' : '' }}.</p>
                    </div>
                </li>
            @endforelse
        </ul>
        {{ $tickets->links() }}
    </div>
</x-layouts.app>
