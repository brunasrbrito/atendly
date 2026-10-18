<x-layouts.auth title="Nova senha">
    <h1>Criar nova senha</h1>
    <p class="muted" style="margin-bottom:1.75rem">Use pelo menos 8 caracteres, com letras e números.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field">
            <label for="email">E-mail</label>
            <input class="input @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username">
            <x-field-error name="email" />
        </div>
        <div class="field">
            <label for="password">Nova senha</label>
            <input class="input @error('password') is-invalid @enderror" type="password" id="password" name="password" required autofocus autocomplete="new-password" minlength="8">
            <x-field-error name="password" />
        </div>
        <div class="field">
            <label for="password_confirmation">Confirmar nova senha</label>
            <input class="input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" minlength="8">
        </div>
        <button class="btn btn--primary btn--block btn--lg">Salvar nova senha</button>
    </form>
</x-layouts.auth>
