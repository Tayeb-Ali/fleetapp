<footer class="mt-auto border-t border-slate-200 bg-white/60 dark:border-slate-800 dark:bg-slate-900/50">
    <div class="mx-auto w-full max-w-5xl px-4 py-8 lg:max-w-7xl">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-indigo-600 text-white" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7h10v8H3z" />
                        <path d="M13 10h4l3 3v2h-7z" />
                        <circle cx="7" cy="17" r="1.6" />
                        <circle cx="17" cy="17" r="1.6" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-bold">{{ __('app.name') }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('app.tagline') }} · {{ __('app.company') }}</p>
                </div>
            </div>

            @auth
                <nav class="flex flex-wrap gap-x-5 gap-y-2 text-sm" aria-label="{{ __('app.dashboard') }}">
                    @foreach ([['dashboard', 'nav.overview'], ['vehicles.index', 'nav.vehicles'], ['reports.index', 'nav.reports'], ['settings.index', 'nav.settings']] as [$route, $label])
                        @if (Route::has($route))
                            <a href="{{ route($route) }}" class="text-slate-600 transition-colors hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-400">{{ __($label) }}</a>
                        @endif
                    @endforeach
                </nav>
            @endauth
        </div>

        <div class="mt-6 flex flex-col gap-2 border-t border-slate-200 pt-4 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ __('app.company') }} · {{ __('app.all_rights_reserved') }}</p>
            <p class="flex flex-wrap items-center gap-1.5">
                <span>{{ __('app.developed_by') }}</span>
                <a href="https://www.smart.sd" target="_blank" rel="noopener" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">www.smart.sd</a>
                <span class="font-semibold">SmartTeam</span>
            </p>
        </div>
    </div>
</footer>
