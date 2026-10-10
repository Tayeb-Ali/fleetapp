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
<body class="flex min-h-screen flex-col bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-sans">
<header class="lg:sticky lg:top-0 lg:z-40 lg:border-b lg:border-slate-200 lg:bg-white/80 lg:backdrop-blur dark:lg:border-slate-800 dark:lg:bg-slate-950/80">
    <div class="mx-auto w-full max-w-5xl px-4 pt-4 lg:max-w-7xl lg:py-0">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:h-16 lg:gap-4">
        <div class="flex min-w-0 items-center gap-2.5">
            <span class="hidden h-9 w-9 shrink-0 place-items-center rounded-xl bg-indigo-600 text-white lg:grid" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h10v8H3z"/><path d="M13 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/></svg>
            </span>
            <div class="min-w-0">
                <h1 class="truncate text-lg font-bold">{{ __('app.name') }}</h1>
                @auth
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->name }} · {{ __('roles.'.auth()->user()->role) }}</p>
                @endauth
            </div>
        </div>
        @auth
            <nav class="hidden items-center gap-1 text-sm font-semibold lg:flex" aria-label="{{ __('app.dashboard') }}">
                <x-layouts.nav-links variant="desktop" />
            </nav>
        @endauth
        <div class="flex flex-wrap items-center gap-2 sm:shrink-0 lg:shrink-0">
            <x-language-switch />
            <button type="button" onclick="toggleTheme()" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-300 text-slate-500 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" title="{{ __('app.dark_mode') }}" aria-label="{{ __('app.dark_mode') }}" aria-pressed="false">
                <svg class="h-4 w-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                <svg class="hidden h-4 w-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            @auth
                <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                    @csrf
                    <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-full bg-slate-200 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>
                        <span class="hidden sm:inline">{{ __('app.sign_out') }}</span>
                    </button>
                </form>
            @endauth
        </div>
        </div>
    </div>
</header>

@auth
<nav class="mx-auto flex w-full max-w-5xl gap-1 overflow-x-auto px-4 py-2 text-sm font-semibold lg:hidden">
    <x-layouts.nav-links variant="mobile" />
</nav>
@endauth

<main class="mx-auto w-full max-w-5xl flex-1 px-4 pb-16 lg:max-w-7xl">
    @if (session('status'))
        <div class="my-3 p-3 rounded-xl bg-emerald-100 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-100 text-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="my-3 p-3 rounded-xl bg-red-100 text-red-900 dark:bg-red-900 dark:text-red-100 text-sm">{{ session('error') }}</div>
    @endif
    {{ $slot }}
</main>

<x-layouts.footer />

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
