<div>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-lg font-bold">{{ __('finance.currencies_title') }}</h1>
        <a href="{{ route('settings.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 underline">{{ __('nav.settings') }}</a>
    </div>

    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('finance.currencies_help') }}</p>

    <div class="mt-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-sm">
        {{ __('finance.base_currency') }}: <b dir="ltr">{{ $base }}</b>
        @unless ($canChangeBase)
            <span class="ms-2 text-xs text-amber-600 dark:text-amber-400">({{ __('finance.base_locked_hint') }})</span>
        @endunless
    </div>

    @if (session('status'))
        <div class="my-3 rounded-xl bg-green-100 dark:bg-green-900 px-3 py-2 text-sm text-green-800 dark:text-green-100">{{ session('status') }}</div>
    @endif

    @error('form.code')<div class="my-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>@enderror
    @error('form')<div class="my-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>@enderror

    <form wire:submit="add" class="mt-4 grid gap-2 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('finance.code') }}
            <input type="text" wire:model="form.code" dir="ltr" maxlength="8" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm uppercase">
        </label>
        @error('form.code')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('finance.name_ar') }}
            <input type="text" wire:model="form.name_ar" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm">
        </label>
        <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('finance.name_en') }}
            <input type="text" wire:model="form.name_en" dir="ltr" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm">
        </label>
        <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('finance.symbol') }}
            <input type="text" wire:model="form.symbol" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm">
        </label>
        <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('finance.decimals') }}
            <input type="number" wire:model="form.decimals" min="0" max="6" dir="ltr" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm">
        </label>
        <label class="block text-xs text-slate-500 dark:text-slate-400">{{ __('finance.sort_order') }}
            <input type="number" wire:model="form.sort_order" min="0" max="9999" dir="ltr" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm">
        </label>
        <label class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <input type="checkbox" wire:model="form.is_active" class="accent-indigo-600"> {{ __('finance.active') }}
        </label>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">{{ __('finance.add_currency') }}</button>
        </div>
    </form>

    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-950 text-xs text-slate-500 dark:text-slate-400">
                <tr>
                    <th class="px-3 py-2 text-start">{{ __('finance.code') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('finance.name_ar') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('finance.name_en') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('finance.symbol') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('finance.decimals') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('finance.sort_order') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('finance.active') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('actions.save') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($rows as $code => $row)
                    <tr wire:key="currency-{{ $code }}">
                        <td class="px-3 py-2 font-mono font-semibold" dir="ltr">
                            {{ $code }}
                            @if ($code === $base)
                                <span class="ms-1 rounded-full bg-indigo-100 dark:bg-indigo-900 px-1.5 text-[10px] text-indigo-700 dark:text-indigo-200">{{ __('finance.base') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2"><input type="text" wire:model="rows.{{ $code }}.name_ar" class="w-40 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-sm"></td>
                        <td class="px-3 py-2"><input type="text" wire:model="rows.{{ $code }}.name_en" dir="ltr" class="w-40 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-sm"></td>
                        <td class="px-3 py-2"><input type="text" wire:model="rows.{{ $code }}.symbol" class="w-16 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-sm"></td>
                        <td class="px-3 py-2"><input type="number" wire:model="rows.{{ $code }}.decimals" min="0" max="6" dir="ltr" class="w-16 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-sm"></td>
                        <td class="px-3 py-2"><input type="number" wire:model="rows.{{ $code }}.sort_order" min="0" max="9999" dir="ltr" class="w-20 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-sm"></td>
                        <td class="px-3 py-2">
                            <button wire:click="toggle('{{ $code }}')" class="rounded-lg px-2 py-1 text-xs {{ $row['is_active'] ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $row['is_active'] ? __('finance.active') : __('finance.inactive') }}
                            </button>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex gap-1">
                                <button wire:click="saveRow('{{ $code }}')" class="rounded-lg bg-indigo-600 px-2 py-1 text-xs text-white">{{ __('actions.save') }}</button>
                                @if ($code !== $base && $canChangeBase)
                                    <button wire:click="setBase('{{ $code }}')" class="rounded-lg bg-slate-200 dark:bg-slate-800 px-2 py-1 text-xs">{{ __('finance.set_base') }}</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
