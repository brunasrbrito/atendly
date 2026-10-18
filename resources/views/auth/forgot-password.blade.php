<x-layouts.auth title="Esqueci a senha">
    <a href="{{ route('login') }}" class="back-link"><x-icon name="arrow-left" /> Voltar para o login</a>
    <h1>Esqueceu a senha?</h1>
    <p class="muted" style="margin-bottom:1.75rem">Informe o e-mail da sua conta. Se ele estiver cadastrado, enviaremos um link para criar uma nova senha.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <label for="email">E-mail</label>
            <input class="input @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="voce@empresa.com">
            <x-field-error name="email" />
        </div>
        <button class="btn btn--primary btn--block btn--lg">Enviar link de redefinição</button>
    </form>
</x-layouts.auth>
