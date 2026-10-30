<div>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-lg font-bold">{{ __('finance.rates_title') }}</h1>
        <a href="{{ route('settings.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 underline">{{ __('nav.settings') }}</a>
    </div>

    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('finance.rates_help') }}</p>

    @if (session('status'))
        <div class="my-3 rounded-xl bg-green-100 dark:bg-green-900 px-3 py-2 text-sm text-green-800 dark:text-green-100">{{ session('status') }}</div>
    @endif

    @if ($missing !== [])
        <div class="my-3 rounded-xl bg-amber-100 dark:bg-amber-900 px-3 py-2 text-sm text-amber-800 dark:text-amber-100">
            {{ __('finance.missing_rates') }}: <b dir="ltr">{{ implode(', ', $missing) }}</b>
        </div>
    @endif

    <div class="mt-4 flex flex-wrap items-end gap-2 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
        <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('finance.period') }}
            <input type="month" wire:model.live="period" class="mt-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm" dir="ltr">
        </label>
        <button wire:click="copyPrevious" class="rounded-xl bg-slate-200 dark:bg-slate-800 px-3 py-2 text-sm font-semibold">{{ __('finance.copy_previous', ['period' => $previousPeriod]) }}</button>
        <button wire:click="save" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('actions.save') }}</button>
    </div>

    @error('rates')<div class="my-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>@enderror

    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-950 text-xs text-slate-500 dark:text-slate-400">
                <tr>
                    <th class="px-3 py-2 text-start">{{ __('finance.currency') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('finance.rate_per_base') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('finance.or_derive') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($currencies as $currency)
                    @php $code = $currency['code']; @endphp
                    <tr wire:key="rate-{{ $code }}">
                        <td class="px-3 py-2">
                            <b class="font-mono" dir="ltr">{{ $code }}</b>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $currency['name'] }}</span>
                            @if ($currency['is_base'])
                                <span class="rounded-full bg-indigo-100 dark:bg-indigo-900 px-1.5 text-[10px] text-indigo-700 dark:text-indigo-200">{{ __('finance.base') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500 dark:text-slate-400" dir="ltr">1 {{ $base ?? '' }}</span>
                                <span class="text-slate-400">=</span>
                                <input type="number" step="0.000001" min="0" wire:model="rates.{{ $code }}" @if ($currency['is_base']) disabled @endif dir="ltr" class="w-32 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-sm disabled:opacity-60">
                                <span class="font-mono text-xs" dir="ltr">{{ $code }}</span>
                            </div>
                            @error('rates.'.$code)<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </td>
                        <td class="px-3 py-2">
                            @unless ($currency['is_base'])
                                <div class="flex flex-wrap items-center gap-1 text-xs">
                                    <input type="number" step="0.01" min="0" wire:model="localAmounts.{{ $code }}" placeholder="{{ __('finance.local_amount') }}" dir="ltr" class="w-24 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1">
                                    <span class="text-slate-400">=</span>
                                    <input type="number" step="0.01" min="0" wire:model="baseAmounts.{{ $code }}" placeholder="{{ __('finance.base_amount') }}" dir="ltr" class="w-24 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1">
                                    <button wire:click="derive('{{ $code }}')" class="rounded-lg bg-slate-200 dark:bg-slate-800 px-2 py-1">{{ __('finance.derive') }}</button>
                                </div>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
