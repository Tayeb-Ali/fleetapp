<div class="w-full max-w-sm">
    <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-xl backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sm:p-8">
        <div class="flex flex-col items-center text-center">
            <div class="grid h-14 w-14 place-items-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 16V7a1 1 0 0 1 1-1h11v10" />
                    <path d="M14 9h4l4 4v3h-2" />
                    <path d="M2 16h2m4 0h8" />
                    <circle cx="6.5" cy="17.5" r="1.8" />
                    <circle cx="17.5" cy="17.5" r="1.8" />
                </svg>
            </div>
            <h1 class="mt-4 text-xl font-bold leading-tight">{{ __('app.name') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('app.sign_in') }}</p>
        </div>

        <form wire:submit="login" class="mt-6 space-y-4" novalidate>
            <div>
                <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('auth.username') }}</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-slate-400 dark:text-slate-500" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input
                        id="username"
                        wire:model="username"
                        type="text"
                        autocomplete="username"
                        autofocus
                        @error('username') aria-invalid="true" aria-describedby="username-error" @enderror
                        class="h-11 w-full rounded-xl border bg-slate-50 ps-10 pe-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:bg-slate-950 @error('username') border-red-400 dark:border-red-500 @else border-slate-200 dark:border-slate-700 @enderror"
                    >
                </div>
                @error('username')
                    <p id="username-error" class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('auth.password') }}</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-slate-400 dark:text-slate-500" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    </span>
                    <input
                        id="password"
                        wire:model="password"
                        type="password"
                        autocomplete="current-password"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        class="h-11 w-full rounded-xl border bg-slate-50 ps-10 pe-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:bg-slate-950 @error('password') border-red-400 dark:border-red-500 @else border-slate-200 dark:border-slate-700 @enderror"
                    >
                </div>
                @error('password')
                    <p id="password-error" class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="login"
                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/50 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <svg wire:loading wire:target="login" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-90" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                </svg>
                {{ __('actions.login') }}
            </button>
        </form>
    </div>

    <p class="mt-5 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('app.company') }} · {{ __('app.tagline') }}</p>
</div>
