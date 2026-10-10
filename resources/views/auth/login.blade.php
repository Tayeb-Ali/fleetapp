<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ __('app.sign_in') }} — {{ __('app.name') }}</title>
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
    </script>
</head>
<body class="min-h-dvh bg-gradient-to-br from-slate-100 via-slate-100 to-slate-200 font-sans text-slate-900 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900 dark:text-slate-100">
    <div class="flex min-h-dvh flex-col">
        <header class="flex justify-end p-4">
            <x-language-switch />
        </header>
        <main class="flex flex-1 items-center justify-center px-4 pb-12">
            <livewire:auth.login />
        </main>
        <x-layouts.footer />
    </div>
    @livewireScripts
</body>
</html>
