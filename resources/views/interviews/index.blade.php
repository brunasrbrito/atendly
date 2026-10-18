<x-layouts.app title="Entrevistas">
    <div class="page-header">
        <div class="page-header__titles">
            <h1>Entrevistas do diagnóstico</h1>
            <p>Registros sem identificação: cada participante é referido apenas por um código e um perfil genérico.</p>
        </div>
        <a href="{{ route('interviews.export') }}" class="btn"><x-icon name="download" /> Exportar CSV</a>
        <a href="{{ route('interviews.create') }}" class="btn btn--primary"><x-icon name="plus" /> Nova entrevista</a>
    </div>

    <section class="card" style="margin-bottom:1rem">
        <div class="card__header"><h2>Temas mencionados</h2><span class="small muted">clique para filtrar</span></div>
        <div class="card__body chips">
            <a href="{{ route('interviews.index') }}" @class(['badge', 'badge--article' => ! $theme])>Todos</a>
            @foreach ($themes as $key => $label)
                <a href="{{ route('interviews.index', ['tema' => $key]) }}" @class(['badge', 'badge--article' => $theme === $key])>{{ $label }} · {{ $themeCounts[$key] }}</a>
            @endforeach
        </div>
    </section>

    <div class="card card--flush">
        <div class="table-wrap" tabindex="0" role="region" aria-label="Tabela com rolagem horizontal">
            <table class="table">
                <thead><tr><th>Código</th><th>Perfil</th><th>Data</th><th>Temas</th></tr></thead>
                <tbody>
                @forelse ($interviews as $interview)
                    <tr>
                        <td><a class="table__title mono" href="{{ route('interviews.show', $interview) }}">{{ $interview->code }}</a> <x-synthetic-badge :when="$interview->is_synthetic" /></td>
                        <td>{{ $interview->profile }}</td>
                        <td>{{ $interview->conducted_on->format('d/m/Y') }}</td>
                        <td class="small">{{ collect($interview->themes)->map(fn ($t) => $themes[$t] ?? $t)->implode(' · ') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty">Nenhuma entrevista registrada.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
