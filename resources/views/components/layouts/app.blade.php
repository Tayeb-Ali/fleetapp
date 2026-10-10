<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ __('app.name') }} — {{ config('app.name', 'Fleet') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @livewireStyles
    <script>
        // Dark mode: persist in localStorage, toggle .dark on <html>
        try {
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
        function toggleTheme() {
            const el = document.documentElement;
            el.classList.toggle('dark');
            try { localStorage.setItem('theme', el.classList.contains('dark') ? 'dark' : 'light'); } catch (e) {}
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100 min-h-screen font-sans">
<header class="max-w-5xl mx-auto px-4 pt-4 flex items-center justify-between gap-3">
    <div>
        <h1 class="text-lg font-bold">{{ __('app.name') }}</h1>
        @auth
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->name }} · {{ __('roles.'.auth()->user()->role) }}</p>
        @endauth
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('lang.switch', 'en') }}" class="text-xs px-2 py-1 rounded border {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-300 dark:border-slate-700' }}">EN</a>
        <a href="{{ route('lang.switch', 'ar') }}" class="text-xs px-2 py-1 rounded border {{ app()->getLocale() === 'ar' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-300 dark:border-slate-700' }}">عر</a>
        <button onclick="toggleTheme()" class="text-xs px-2 py-1 rounded border border-slate-300 dark:border-slate-700" title="{{ __('app.dark_mode') }}" aria-label="{{ __('app.dark_mode') }}" aria-pressed="false">◐</button>
        @auth
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-xs px-3 py-1.5 rounded-full bg-slate-200 dark:bg-slate-800">{{ __('app.sign_out') }}</button>
            </form>
        @endauth
    </div>
</header>

@auth
<nav class="max-w-5xl mx-auto px-4 py-2 flex gap-1 overflow-x-auto text-sm font-semibold">
    @php
        $tabs = [
            ['dashboard', 'nav.overview', '/'],
            ['vehicles.index', 'nav.vehicles', '/vehicles'],
            ['reports.index', 'nav.reports', '/reports'],
            ['notifications.index', 'nav.notifications', '/notifications'],
            ['users.index', 'nav.users', '/users'],
            ['audit.index', 'nav.audit', '/audit'],
            ['settings.index', 'nav.settings', '/settings'],
        ];
    @endphp
    @foreach ($tabs as [$route, $label, $fallback])
        @if (Route::has($route))
            <a href="{{ route($route) }}" class="px-3 py-2 rounded-lg whitespace-nowrap {{ request()->routeIs($route.'*') ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow' : 'text-slate-500 dark:text-slate-400' }}">{{ __($label) }}</a>
        @else
            <a href="{{ $fallback }}" class="px-3 py-2 rounded-lg whitespace-nowrap text-slate-500 dark:text-slate-400">{{ __($label) }}</a>
        @endif
    @endforeach
</nav>
@endauth

<main class="max-w-5xl mx-auto px-4 pb-16">
    @if (session('status'))
        <div class="my-3 p-3 rounded-xl bg-emerald-100 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-100 text-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="my-3 p-3 rounded-xl bg-red-100 text-red-900 dark:bg-red-900 dark:text-red-100 text-sm">{{ session('error') }}</div>
    @endif
    {{ $slot }}
</main>

<div id="toast" class="fixed bottom-5 left-1/2 -translate-x-1/2 bg-black/80 text-white text-sm px-4 py-2 rounded-full hidden z-50"></div>
<script>
    window.toast = function (msg) {
        const e = document.getElementById('toast');
        if (!e) return;
        e.textContent = msg;
        e.classList.remove('hidden');
        clearTimeout(e._t);
        e._t = setTimeout(() => e.classList.add('hidden'), 3200);
    };
    document.addEventListener('livewire:init', () => {
        Livewire.on('toast', (msg) => window.toast(msg));
    });
</script>
@livewireScripts
</body>
</html>
