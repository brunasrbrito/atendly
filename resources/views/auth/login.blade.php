<x-layouts.auth title="Entrar">
    <h1>Bem-vindo de volta</h1>
    <p class="muted" style="margin-bottom:1.75rem">Entre com sua conta da equipe de suporte.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label for="email">E-mail</label>
            <input class="input @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="voce@empresa.com">
            <x-field-error name="email" />
        </div>
        <div class="field">
            <div class="row" style="justify-content:space-between">
                <label for="password" class="field__label">Senha</label>
                <a href="{{ route('password.request') }}" class="small" style="font-weight:600">Esqueceu a senha?</a>
            </div>
            <input class="input" type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <label class="check" style="margin-bottom:1.25rem"><input type="checkbox" name="remember" value="1"> Manter conectado</label>
        <button class="btn btn--primary btn--block btn--lg">Entrar</button>
    </form>
</x-layouts.auth>
