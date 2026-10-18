<x-layouts.app :title="'Entrevista '.$interview->code">
    <div class="page-header">
        <div class="page-header__titles">
            <a href="{{ route('interviews.index') }}" class="back-link"><x-icon name="arrow-left" /> Entrevistas</a>
            <div class="row" style="margin:.35rem 0"><x-synthetic-badge :when="$interview->is_synthetic" /></div>
            <h1>Entrevista {{ $interview->code }}</h1>
            <p>{{ $interview->profile }} · {{ $interview->conducted_on->format('d/m/Y') }} · consentimento {{ $interview->consent_recorded ? 'registrado' : 'não registrado' }}</p>
        </div>
        <a href="{{ route('interviews.edit', $interview) }}" class="btn">Editar</a>
        <form method="POST" action="{{ route('interviews.destroy', $interview) }}" data-confirm="Excluir esta entrevista?">
            @csrf @method('DELETE')
            <button class="btn btn--danger">Excluir</button>
        </form>
    </div>

    <div class="card form-card">
        <div class="card__body">
            <div class="chips" style="margin-bottom:1rem">
                @foreach ($interview->themes ?? [] as $t)
                    <span class="badge badge--article">{{ $themes[$t] ?? $t }}</span>
                @endforeach
            </div>
            <div class="prewrap">{{ $interview->notes }}</div>
        </div>
    </div>
</x-layouts.app>
