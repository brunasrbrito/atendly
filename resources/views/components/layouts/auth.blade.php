@props(['title' => 'Entrar'])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1a1d">
    <title>{{ $title }} · Atendly</title>
    @include('partials.favicon')
    <script>
        try { const t = localStorage.getItem('atendly:theme'); if (t) document.documentElement.dataset.theme = t; } catch (e) {}
    </script>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
<a href="#conteudo" class="skip-link">Pular para o conteúdo</a>
<main class="login">
    <section class="login__brand-panel" aria-hidden="true">
        <div class="logo-wordmark"><x-logo :size="36" /> Atendly</div>

        <div>
            <h2>Nenhum chamado esquecido na fila.</h2>
            <p>Acompanhe novos chamados assim que chegam e encontre em segundos como a equipe resolveu problemas parecidos.</p>

            <ul class="login__features">
                <li><x-icon name="bell" /><div><strong>Alerta de novos chamados</strong><span>Aviso na tela, som e notificação do navegador.</span></div></li>
                <li><x-icon name="lightbulb" /><div><strong>Soluções semelhantes</strong><span>Sugestões automáticas a partir do histórico.</span></div></li>
                <li><x-icon name="chart" /><div><strong>Indicadores de atendimento</strong><span>Tempo até percepção, resolução e recorrência.</span></div></li>
            </ul>
        </div>

        <span class="small" style="color:#8a9c99">Central de suporte de TI</span>
    </section>

    <section class="login__form-panel" id="conteudo" tabindex="-1">
        <div class="login__card">
            <div class="login__brand">
                <x-logo :size="34" /> Atendly
            </div>

            @if (session('status'))
                <div class="alert alert--success" role="status"><x-icon name="check" /> {{ session('status') }}</div>
            @endif

            {{ $slot }}
        </div>
    </section>
</main>
</body>
</html>
