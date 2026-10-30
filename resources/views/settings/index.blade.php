<x-layouts.app>
    @if ((auth()->user()->role ?? '') === 'admin')
        <div class="mb-3 flex flex-wrap gap-2">
            <a href="{{ route('settings.currencies') }}" class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-2 text-sm font-semibold">{{ __('finance.currencies_title') }}</a>
            <a href="{{ route('settings.exchange-rates') }}" class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-2 text-sm font-semibold">{{ __('finance.rates_title') }}</a>
        </div>
    @endif
    <livewire:admin.settings-editor />
</x-layouts.app>
