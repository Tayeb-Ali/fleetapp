<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
    <b>{{ __('actions.register') }}</b>
    <div class="mt-3 space-y-1">
        @foreach ($fields as $key)
            @php
                $meta = $labels[$key] ?? [$key, 'text'];
                $type = is_array($meta) ? ($meta[1] ?? 'text') : 'text';
                $isRequired = in_array($key, $required, true);
            @endphp
            @if ($key === 'modification_cost' && ($data['mod'] ?? 'No') !== 'Yes')
                @continue
            @endif
            <label class="block text-xs text-slate-500">{{ __('vehicles.'.$key) }}{{ $isRequired ? ' *' : '' }}
                @if ($type === 'area')
                    <textarea wire:model="data.{{ $key }}" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm text-slate-900 dark:text-slate-100"></textarea>
                @elseif ($type === 'currency')
                    <select wire:model.live="data.{{ $key }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm text-slate-900 dark:text-slate-100">
                        @foreach ($currencyOptions as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @elseif ($type === 'yesno')
                    <select wire:model.live="data.{{ $key }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm text-slate-900 dark:text-slate-100">
                        <option value="">{{ __('vehicles.not_set') }}</option>
                        <option value="Yes">{{ __('finance.yes') }}</option>
                        <option value="No">{{ __('finance.no') }}</option>
                    </select>
                @elseif ($key === 'mod')
                    <select wire:model.live="data.mod" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm text-slate-900 dark:text-slate-100">
                        <option value="No">{{ __('finance.no') }}</option>
                        <option value="Yes">{{ __('finance.yes') }}</option>
                    </select>
                @else
                    <input @if ($type === 'date') wire:model.live="data.{{ $key }}" @else wire:model="data.{{ $key }}" @endif type="{{ $type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text') }}" @if ($type === 'number') step="0.000001" min="0" @endif class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm text-slate-900 dark:text-slate-100">
                @endif
            </label>
            @error('data.'.$key)<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        @endforeach

        <label class="block text-xs text-slate-500">
            {{ __('attachments.purchase_proof') }}
            <input type="file" wire:model="proofs" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" class="mt-1 w-full text-xs">
        </label>
        @error('proofs')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        @error('proofs.*')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    </div>
    <button wire:click="save" class="mt-3 w-full px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold" wire:loading.attr="disabled" wire:target="save,proofs">
        <span wire:loading.remove wire:target="save">{{ __('actions.save') }}</span>
        <span wire:loading wire:target="save">{{ __('attachments.uploading') }}</span>
    </button>
</div>
