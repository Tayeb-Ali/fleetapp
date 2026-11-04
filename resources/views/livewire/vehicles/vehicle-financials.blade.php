<div class="mt-5">
    @php
        $money = function ($value, string $currency) {
            return $value === null ? '—' : number_format((float) $value, 2).' '.$currency;
        };
        $base = $baseCurrency;
    @endphp

    <h2 class="mb-2 text-xs text-slate-500 dark:text-slate-400">{{ __('finance.summary_title') }}</h2>

    @if (session('financials-ok'))
        <div class="mb-2 rounded-xl bg-green-100 dark:bg-green-900 px-3 py-2 text-sm text-green-800 dark:text-green-100">{{ session('financials-ok') }}</div>
    @endif

    @if ($breakdown->hasMissingRates())
        <div class="mb-2 rounded-xl bg-amber-100 dark:bg-amber-900 px-3 py-2 text-sm text-amber-800 dark:text-amber-100">
            {{ __('finance.missing_rates_warning') }}:
            <b dir="ltr">{{ implode(', ', $breakdown->missingRates) }}</b>
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr>
                    <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ __('finance.purchase') }}</td>
                    <td class="px-3 py-2 text-end" dir="ltr">
                        {{ $breakdown->purchaseNative !== null ? number_format($breakdown->purchaseNative, 2).' '.$breakdown->purchaseCurrency : '—' }}
                    </td>
                    <td class="px-3 py-2 text-end text-xs text-slate-500 dark:text-slate-400" dir="ltr">
                        @if ($breakdown->purchaseRate !== null && $breakdown->purchaseRate != 1)
                            × {{ rtrim(rtrim(number_format($breakdown->purchaseRate, 6, '.', ''), '0'), '.') }}
                        @endif
                    </td>
                    <td class="px-3 py-2 text-end font-medium" dir="ltr">{{ $money($breakdown->purchaseBase, $base) }}</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ __('finance.shipping') }}</td>
                    <td class="px-3 py-2 text-end" dir="ltr">
                        {{ $breakdown->shippingNative !== null ? number_format($breakdown->shippingNative, 2).' '.$breakdown->shippingCurrency : '—' }}
                    </td>
                    <td class="px-3 py-2 text-end text-xs text-slate-500 dark:text-slate-400" dir="ltr">
                        @if ($breakdown->shippingRate !== null && $breakdown->shippingRate != 1)
                            × {{ rtrim(rtrim(number_format($breakdown->shippingRate, 6, '.', ''), '0'), '.') }}
                        @endif
                    </td>
                    <td class="px-3 py-2 text-end font-medium" dir="ltr">{{ $money($breakdown->shippingBase, $base) }}</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ __('finance.landed') }}</td>
                    <td class="px-3 py-2 text-end" colspan="2"></td>
                    <td class="px-3 py-2 text-end font-medium" dir="ltr">{{ $money($breakdown->landedBase, $base) }}</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ __('finance.modification') }}</td>
                    <td class="px-3 py-2 text-end" colspan="2"></td>
                    <td class="px-3 py-2 text-end font-medium" dir="ltr">{{ $money($breakdown->modificationBase, $base) }}</td>
                </tr>
                <tr class="bg-slate-50 dark:bg-slate-950">
                    <td class="px-3 py-2 font-bold">{{ __('finance.total_cost') }}</td>
                    <td class="px-3 py-2 text-end" colspan="2"></td>
                    <td class="px-3 py-2 text-end font-bold" dir="ltr">{{ $money($breakdown->totalCost, $base) }}</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 text-slate-500 dark:text-slate-400">{{ __('finance.sale') }}</td>
                    <td class="px-3 py-2 text-end" dir="ltr">
                        {{ $breakdown->saleNative !== null ? number_format($breakdown->saleNative, 2).' '.$breakdown->saleCurrency : '—' }}
                    </td>
                    <td class="px-3 py-2 text-end text-xs text-slate-500 dark:text-slate-400" dir="ltr">
                        @if ($breakdown->saleRate !== null && $breakdown->saleRate != 1)
                            × {{ rtrim(rtrim(number_format($breakdown->saleRate, 6, '.', ''), '0'), '.') }}
                        @endif
                    </td>
                    <td class="px-3 py-2 text-end font-medium" dir="ltr">{{ $money($breakdown->saleBase, $base) }}</td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-bold">{{ __('finance.profit') }}</td>
                    <td class="px-3 py-2 text-end" colspan="2"></td>
                    <td class="px-3 py-2 text-end font-bold {{ $breakdown->profit !== null && $breakdown->profit < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-700 dark:text-green-400' }}" dir="ltr">
                        {{ $money($breakdown->profit, $base) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($editable !== [])
        <form wire:submit="save" class="mt-3 space-y-3">
            @foreach ($fieldGroups as $group)
                @php
                    $visible = array_values(array_filter($group['fields'], fn ($f) => in_array($f['key'], $editable, true)));
                @endphp
                @if ($visible !== [])
                    <fieldset class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3">
                        <legend class="px-1 text-sm font-semibold">{{ __($group['label']) }}</legend>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($visible as $field)
                                @php $key = $field['key']; @endphp
                                @if ($key === 'modification_cost' && ($form['mod'] ?? 'No') !== 'Yes')
                                    @continue
                                @endif
                                <label class="block text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('vehicles.'.$key) }}
                                    @if ($field['type'] === 'currency')
                                        <select wire:model.live="form.{{ $key }}" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm">
                                            @foreach ($currencyOptions as $code => $label)
                                                <option value="{{ $code }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($field['type'] === 'yesno')
                                        <select wire:model.live="form.{{ $key }}" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm">
                                            <option value="">{{ __('vehicles.not_set') }}</option>
                                            <option value="Yes">{{ __('finance.yes') }}</option>
                                            <option value="No">{{ __('finance.no') }}</option>
                                        </select>
                                    @elseif ($field['type'] === 'mod')
                                        <select wire:model.live="form.mod" class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm">
                                            <option value="No">{{ __('finance.no') }}</option>
                                            <option value="Yes">{{ __('finance.yes') }}</option>
                                        </select>
                                    @else
                                        <input
                                            type="{{ $field['type'] === 'date' ? 'date' : ($field['type'] === 'number' ? 'number' : 'text') }}"
                                            @if ($field['type'] === 'number') step="0.000001" min="0" @endif
                                            @if ($field['type'] === 'date')
                                                wire:model.live="form.{{ $key }}"
                                            @else
                                                wire:model="form.{{ $key }}"
                                            @endif
                                            dir="{{ $field['type'] === 'number' || $field['type'] === 'date' ? 'ltr' : 'auto' }}"
                                            class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-sm">
                                    @endif
                                    @error('form.'.$key)<span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>@enderror
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
            @endforeach

            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">{{ __('actions.save') }}</button>
        </form>
    @endif

    <h2 class="mb-2 mt-4 text-xs text-slate-500 dark:text-slate-400">{{ __('attachments.proofs_title') }}</h2>
    <div class="grid gap-3 lg:grid-cols-2">
        @foreach (\App\Domain\Vehicle\Enums\AttachmentCategory::proofValues() as $category)
            <livewire:vehicles.financial-proofs
                :vin="$vin"
                :category="$category"
                :key="'proof-'.$vin.'-'.$category" />
        @endforeach
    </div>
</div>
