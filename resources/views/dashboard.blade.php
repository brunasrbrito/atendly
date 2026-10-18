@php use App\Support\Highlighter; @endphp
<x-layouts.app title="Indicadores">
    <div class="page-header">
        <div class="page-header__titles">
            <h1>Indicadores</h1>
            <p>Quanto tempo os chamados esperam para ser percebidos, quanto levam para ser resolvidos e o que mais se repete.</p>
        </div>
        <div class="page-header__actions" style="align-items:center">
            @if (config('atendly.show_test_marks'))
                <form method="GET">
                    <input type="hidden" name="dias" value="{{ $days }}">
                    <select class="select select--sm" name="dados" onchange="this.form.submit()" aria-label="Origem dos dados" style="width:auto">
                        <option value="">Todos os dados</option>
                        <option value="sinteticos" @selected($origin === 'sinteticos')>Somente dados de teste</option>
                        <option value="reais" @selected($origin === 'reais')>Somente dados reais</option>
                    </select>
                </form>
            @endif
            <nav class="segmented" style="margin:0" aria-label="Período">
                @foreach ([7, 30, 90] as $d)
                    <a href="{{ route('dashboard', array_filter(['dias' => $d, 'dados' => $origin])) }}" @class(['segmented__item', 'is-active' => $days === $d])>{{ $d }} dias</a>
                @endforeach
            </nav>
        </div>
    </div>

    @if (config('atendly.show_test_marks'))
        <p class="disclaimer">
            Com a base atual, estes números testam o protótipo e as hipóteses do diagnóstico.
            Indicadores calculados sobre dados de teste <strong>não</strong> descrevem o desempenho real do suporte.
        </p>
    @endif

    <div class="grid grid--4" style="margin-bottom:1.25rem">
        <div @class(['card kpi', 'kpi--alert' => $m['unacknowledged'] > 0])>
            <div class="kpi__top"><span class="kpi__icon"><x-icon name="alarm" /></span><span class="kpi__label">Aguardando agora</span></div>
            <div class="kpi__value">{{ $m['unacknowledged'] }}</div>
            <div class="kpi__hint"><a href="{{ route('tickets.index', ['ver' => 'pendentes']) }}">Ver na fila <x-icon name="arrow-right" style="width:12px;vertical-align:-2px" /></a></div>
        </div>
        <div class="card kpi">
            <div class="kpi__top"><span class="kpi__icon"><x-icon name="timer" /></span><span class="kpi__label">Tempo até percepção</span></div>
            <div class="kpi__value">{{ Highlighter::duration($m['perception_median']) }}</div>
            <div class="kpi__hint">mediana · 90% em até {{ Highlighter::duration($m['perception_p90']) }}</div>
        </div>
        <div class="card kpi">
            <div class="kpi__top"><span class="kpi__icon"><x-icon name="trend" /></span><span class="kpi__label">Percebidos acima da meta</span></div>
            <div class="kpi__value">{{ number_format($m['late_perception_rate'], 0, ',', '.') }}%</div>
            <div class="kpi__hint">meta de resposta de cada categoria</div>
        </div>
        <div class="card kpi">
            <div class="kpi__top"><span class="kpi__icon"><x-icon name="book-check" /></span><span class="kpi__label">Resolvidos com a base</span></div>
            <div class="kpi__value">{{ number_format($m['knowledge_reuse_rate'], 0, ',', '.') }}%</div>
            <div class="kpi__hint">{{ $m['resolved'] }} resolvidos de {{ $m['total'] }} no período</div>
        </div>
    </div>

    <div class="grid grid--2" style="margin-bottom:1.25rem">
        <section class="card">
            <div class="card__header"><x-icon name="clock" /><h2>Percepção por horário de abertura</h2></div>
            <div class="card__body">
                <div class="chart-box"><canvas id="chart-hour" role="img" aria-label="Tempo até percepção por hora de abertura"></canvas></div>
                <p class="chart-note">Barras em âmbar passam de 1 hora. A linha mostra quantos chamados abrem em cada horário.</p>
            </div>
        </section>
        <section class="card">
            <div class="card__header"><x-icon name="chart" /><h2>Chamados abertos por dia</h2></div>
            <div class="card__body">
                <div class="chart-box"><canvas id="chart-day" role="img" aria-label="Volume diário de chamados"></canvas></div>
                <p class="chart-note">Últimos {{ $days }} dias · {{ $m['total'] }} chamados.</p>
            </div>
        </section>
        <section class="card">
            <div class="card__header"><x-icon name="clipboard" /><h2>Distribuição por categoria</h2></div>
            <div class="card__body"><div class="chart-box chart-box--sm"><canvas id="chart-category" role="img" aria-label="Chamados por categoria"></canvas></div></div>
        </section>
        <section class="card">
            <div class="card__header"><x-icon name="timer" /><h2>Percepção por dia da semana</h2></div>
            <div class="card__body"><div class="chart-box chart-box--sm"><canvas id="chart-weekday" role="img" aria-label="Tempo até percepção por dia da semana"></canvas></div></div>
        </section>
    </div>

    <div class="grid grid--2">
        <section class="card card--flush">
            <div class="card__header"><x-icon name="repeat" /><h2>Problemas que mais se repetem</h2></div>
            <div class="card__body">
                @php($maxUses = max(1, collect($m['recurring'])->max('uses')))
                <ul class="rank">
                    @forelse ($m['recurring'] as $item)
                        <li>
                            <span class="rank__pos">{{ $loop->iteration }}</span>
                            <a href="{{ route('articles.show', $item['id']) }}" class="truncate" style="font-weight:600">{{ $item['title'] }}</a>
                            <span class="rank__count">{{ $item['uses'] }}×</span>
                            <div class="bar"><span style="width: {{ $item['uses'] / $maxUses * 100 }}%"></span></div>
                        </li>
                    @empty
                        <li class="muted" style="display:block">Nenhum artigo aplicado no período.</li>
                    @endforelse
                </ul>
            </div>
        </section>

        <section class="card card--flush">
            <div class="card__header"><x-icon name="clipboard" /><h2>Tempos por categoria</h2></div>
            <div class="card__body table-wrap">
                <table class="table">
                    <thead><tr><th>Categoria</th><th>Chamados</th><th>Até percepção</th><th>Até resolução</th></tr></thead>
                    <tbody>
                    @foreach ($m['by_category'] as $row)
                        <tr>
                            <td><span class="tag" style="--tag: {{ $row['color'] }};color:var(--text);font-weight:600">{{ $row['name'] }}</span></td>
                            <td class="num">{{ $row['count'] }}</td>
                            <td class="num">{{ Highlighter::duration($row['perception_avg']) }}</td>
                            <td class="num">{{ Highlighter::duration($row['resolution_avg']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @php($chartData = array_intersect_key($m, array_flip(['by_hour', 'by_day', 'by_category', 'by_weekday'])))
    <script type="application/json" id="metrics-data">{!! json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
</x-layouts.app>
