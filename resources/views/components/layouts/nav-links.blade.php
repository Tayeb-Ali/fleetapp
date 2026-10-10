@props(['variant' => 'mobile'])

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

    $base = 'px-3 py-2 rounded-lg whitespace-nowrap transition-colors';

    $active = $variant === 'desktop'
        ? 'text-indigo-600 dark:text-indigo-400 font-semibold'
        : 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow';

    $idle = $variant === 'desktop'
        ? 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white'
        : 'text-slate-500 dark:text-slate-400';
@endphp

@foreach ($tabs as [$route, $label, $fallback])
    @if (Route::has($route))
        <a href="{{ route($route) }}" class="{{ $base }} {{ request()->routeIs($route.'*') ? $active : $idle }}">{{ __($label) }}</a>
    @else
        <a href="{{ $fallback }}" class="{{ $base }} {{ $idle }}">{{ __($label) }}</a>
    @endif
@endforeach
