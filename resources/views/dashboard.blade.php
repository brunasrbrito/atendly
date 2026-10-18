@php
    use App\Support\Highlighter;
    // Resumos em texto dos gráficos, para leitores de tela.
    $byHour = collect($m['by_hour'])->filter(fn ($h) => $h['median'] !== null);
    $worstHour = $byHour->sortByDesc('median')->keys()->first();
    $busiestHour = collect($m['by_hour'])->sortByDesc('count')->keys()->first();
    $weekdays = collect($m['by_weekday'])->filter(fn ($v) => $v !== null);
    $worstDay = $weekdays->sortDesc()->keys()->first();
    $bestDay = $weekdays->sort()->keys()->first();
    $topCategory = $m['by_category'][0] ?? null;
@endphp
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
                    <a href="{{ route('dashboard', array_filter(['dias' => $d, 'dados' => $origin])) }}" @class(['segmented__item', 'is-active' => $days === $d]) @if ($days === $d) aria-current="page" @endif>{{ $d }} dias</a>
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
                <div class="chart-box"><canvas id="chart-hour" role="img" aria-label="Tempo até percepção por hora de abertura" aria-describedby="resumo-hora"></canvas></div>
                <p class="sr-only" id="resumo-hora">@if ($worstHour)Maior tempo até percepção para chamados abertos às {{ $worstHour }}: {{ Highlighter::duration($byHour[$worstHour]['median']) }} (mediana). Horário com mais chamados: {{ $busiestHour }}.@else Sem dados no período.@endif</p>
                <p class="chart-note">Barras em âmbar passam de 1 hora. A linha mostra quantos chamados abrem em cada horário.</p>
            </div>
        </section>
        <section class="card">
            <div class="card__header"><x-icon name="chart" /><h2>Chamados abertos por dia</h2></div>
            <div class="card__body">
                <div class="chart-box"><canvas id="chart-day" role="img" aria-label="Volume diário de chamados: {{ $m['total'] }} chamados em {{ $days }} dias, até {{ collect($m['by_day'])->max() }} por dia"></canvas></div>
                <p class="chart-note">Últimos {{ $days }} dias · {{ $m['total'] }} chamados.</p>
            </div>
        </section>
        <section class="card">
            <div class="card__header"><x-icon name="clipboard" /><h2>Distribuição por categoria</h2></div>
            <div class="card__body"><div class="chart-box chart-box--sm"><canvas id="chart-category" role="img" aria-label="Chamados por categoria{{ $topCategory ? '. Mais frequente: '.$topCategory['name'].' com '.$topCategory['count'].' chamados. Tabela completa em Tempos por categoria.' : '' }}"></canvas></div></div>
        </section>
        <section class="card">
            <div class="card__header"><x-icon name="timer" /><h2>Percepção por dia da semana</h2></div>
            <div class="card__body"><div class="chart-box chart-box--sm"><canvas id="chart-weekday" role="img" aria-label="Tempo até percepção por dia da semana{{ $worstDay ? '. Maior: '.$worstDay.' ('.Highlighter::duration($weekdays[$worstDay]).'). Menor: '.$bestDay.' ('.Highlighter::duration($weekdays[$bestDay]).').' : '' }}"></canvas></div></div>
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
            <div class="card__body table-wrap" tabindex="0" role="region" aria-label="Tabela com rolagem horizontal">
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
