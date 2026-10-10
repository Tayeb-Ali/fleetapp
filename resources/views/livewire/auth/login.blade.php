<div class="max-w-sm mx-auto mt-[12vh] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
    <h1 class="font-bold text-lg">{{ __('app.name') }}</h1>
    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">{{ __('app.sign_in') }}</p>
    <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('auth.username') }}
        <input wire:model="username" autocomplete="username" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm mb-2">
    </label>
    @error('username')<span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span>@enderror
    <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('auth.password') }}
        <input wire:model="password" type="password" autocomplete="current-password" wire:keydown.enter="login" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm mb-2">
    </label>
    @error('password')<span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span>@enderror
    <button wire:click="login" class="w-full px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold mt-2">{{ __('actions.login') }}</button>
    <div class="mt-3 text-center text-xs text-slate-500 dark:text-slate-400">
        <a href="{{ route('lang.switch', 'en') }}" class="underline">EN</a> · <a href="{{ route('lang.switch', 'ar') }}" class="underline">عر</a>
    </div>
</div>
