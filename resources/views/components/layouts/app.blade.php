<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1a1d">
    <title>{{ isset($title) ? $title.' · ' : '' }}Atendly</title>
    @include('partials.favicon')
    <script>
        try { const t = localStorage.getItem('atendly:theme'); if (t) document.documentElement.dataset.theme = t; } catch (e) {}
    </script>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body data-poll-url="{{ route('queue.poll') }}" data-latest-id="{{ $latestTicketId }}" data-poll-seconds="{{ config('atendly.poll_seconds') }}">
<div class="shell">
    <aside class="sidebar" aria-label="Menu principal">
        <a href="{{ route('tickets.index') }}" class="sidebar__brand">
            <x-logo :size="34" />
            <span>
                <span class="sidebar__name">Atendly</span>
                <span class="sidebar__tagline">Central de suporte de TI</span>
            </span>
        </a>

        <nav class="nav">
            <span class="sidebar__section">Atendimento</span>
            <a href="{{ route('tickets.index') }}" @class(['nav__link', 'is-active' => request()->routeIs('tickets.*')])>
                <x-icon name="inbox" /> Fila de chamados
                <span class="nav__count {{ $navUnacknowledged ? 'is-alert' : '' }}" data-unack-count @if (! $navUnacknowledged) hidden @endif>{{ $navUnacknowledged }}</span>
            </a>
            <a href="{{ route('search') }}" @class(['nav__link', 'is-active' => request()->routeIs('search')])>
                <x-icon name="search" /> Buscar soluções
            </a>
            <a href="{{ route('articles.index') }}" @class(['nav__link', 'is-active' => request()->routeIs('articles.*')])>
                <x-icon name="book" /> Base de conhecimento
            </a>

            <span class="sidebar__section">Gestão</span>
            <a href="{{ route('dashboard') }}" @class(['nav__link', 'is-active' => request()->routeIs('dashboard')])>
                <x-icon name="chart" /> Indicadores
            </a>
            <a href="{{ route('interviews.index') }}" @class(['nav__link', 'is-active' => request()->routeIs('interviews.*')])>
                <x-icon name="clipboard" /> Entrevistas
            </a>
        </nav>

        <div class="sidebar__footer">
            <x-avatar :name="auth()->user()->name" class="avatar--sidebar" />
            <div class="sidebar__user">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->role?->label() }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn--ghost btn--sm btn--icon" style="color:inherit" title="Sair"><x-icon name="logout" /><span class="sr-only">Sair</span></button>
            </form>
        </div>
    </aside>
    <div class="sidebar-backdrop" data-toggle-sidebar></div>

    <div class="main">
        <header class="topbar">
            <button class="btn btn--ghost btn--icon topbar__menu" data-toggle-sidebar aria-label="Abrir menu"><x-icon name="menu" /></button>
            <form class="topbar__search search-box" action="{{ route('search') }}" role="search">
                <x-icon name="search" />
                <input class="input" type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Buscar soluções anteriores…" aria-label="Buscar soluções" data-search-shortcut>
                <kbd title="Atalho: tecla /">/</kbd>
            </form>
            <div class="spacer"></div>
            <button class="btn btn--ghost btn--icon has-dot" data-enable-notifications hidden title="Ativar notificações do navegador">
                <x-icon name="bell" /><span class="sr-only">Ativar notificações</span>
            </button>
            <button class="btn btn--ghost btn--icon" data-toggle-sound aria-pressed="true" title="Som de novos chamados">
                <x-icon name="volume" data-sound-on /><x-icon name="volume-x" data-sound-off hidden />
                <span class="sr-only" data-sound-label>Som ligado</span>
            </button>
            <button class="btn btn--ghost btn--icon" data-toggle-theme title="Alternar tema claro/escuro">
                <x-icon name="moon" data-theme-icon="dark" /><x-icon name="sun" data-theme-icon="light" />
                <span class="sr-only">Alternar tema</span>
            </button>
        </header>

        <main class="content">
            @if (session('success'))
                <div class="alert alert--success" role="status"><x-icon name="check" /> {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger" role="alert"><x-icon name="lock" /> {{ session('error') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
