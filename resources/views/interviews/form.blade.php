<x-layouts.app :title="$interview->exists ? 'Editar entrevista' : 'Nova entrevista'">
    <div class="page-header">
        <div class="page-header__titles">
            <a href="{{ route('interviews.index') }}" class="back-link"><x-icon name="arrow-left" /> Entrevistas</a>
            <h1>{{ $interview->exists ? 'Editar entrevista '.$interview->code : 'Nova entrevista' }}</h1>
        </div>
    </div>

    <div class="alert alert--info" style="max-width:860px">
        <x-icon name="users" />
        <span>Não registre nome, e-mail, telefone, CPF ou detalhes que permitam identificar o participante. O sistema bloqueia e-mails, telefones e CPFs.</span>
    </div>

    <form method="POST" action="{{ $interview->exists ? route('interviews.update', $interview) : route('interviews.store') }}" class="card form-card">
        @csrf
        @if ($interview->exists) @method('PUT') @endif
        <div class="card__body">
            <div class="grid grid--2">
                <div class="field">
                    <label for="code">Código</label>
                    <input class="input mono" id="code" name="code" value="{{ old('code', $interview->code) }}" required>
                    <x-field-error name="code" />
                </div>
                <div class="field">
                    <label for="conducted_on">Data</label>
                    <input class="input" type="date" id="conducted_on" name="conducted_on" value="{{ old('conducted_on', $interview->conducted_on?->toDateString()) }}" required>
                    <x-field-error name="conducted_on" />
                </div>
            </div>
            <div class="field">
                <label for="profile">Perfil do participante</label>
                <input class="input" id="profile" name="profile" value="{{ old('profile', $interview->profile) }}" required placeholder="Ex.: Técnico N1, Solicitante - área administrativa">
                <x-field-error name="profile" />
            </div>
            <div class="field">
                <span class="field__label" id="temas-label">Temas</span>
                <div class="chips" role="group" aria-labelledby="temas-label">
                    @foreach ($themes as $key => $label)
                        <label class="check"><input type="checkbox" name="themes[]" value="{{ $key }}" @checked(in_array($key, old('themes', $interview->themes ?? [])))> {{ $label }}</label>
                    @endforeach
                </div>
            </div>
            <div class="field">
                <label for="notes">Anotações</label>
                <textarea class="textarea" id="notes" name="notes" required style="min-height:220px">{{ old('notes', $interview->notes) }}</textarea>
                <x-field-error name="notes" />
            </div>
            <label class="check"><input type="checkbox" name="consent_recorded" value="1" @checked(old('consent_recorded', $interview->consent_recorded))> O participante consentiu com o registro anônimo</label>
            <x-field-error name="consent_recorded" />
            <label class="check" style="margin-top:.5rem"><input type="checkbox" name="is_synthetic" value="1" @checked(old('is_synthetic', $interview->is_synthetic))> Registro de teste</label>
        </div>
        <div class="card__footer">
            <button class="btn btn--primary">Salvar</button>
        </div>
    </form>
</x-layouts.app>
