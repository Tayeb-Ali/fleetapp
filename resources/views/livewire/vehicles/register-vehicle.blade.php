<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
    <b>{{ __('actions.register') }}</b>
    <div class="mt-3 space-y-1">
        @foreach ($fields as $key)
            @php
                $meta = $labels[$key] ?? [$key, 'text'];
                $label = is_array($meta) ? ($meta[0] ?? $key) : $meta;
                $type = is_array($meta) ? ($meta[1] ?? 'text') : 'text';
                $isRequired = in_array($key, $required, true);
            @endphp
            <label class="block text-xs text-slate-500">{{ __('vehicles.'.$key) }}{{ $isRequired ? ' *' : '' }}
                @if ($type === 'area')
                    <textarea wire:model="data.{{ $key }}" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm text-slate-900 dark:text-slate-100"></textarea>
                @else
                    <input wire:model="data.{{ $key }}" type="{{ $type }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-sm text-slate-900 dark:text-slate-100">
                @endif
            </label>
            @error('data.'.$key)<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        @endforeach
    </div>
    <button wire:click="save" class="mt-3 w-full px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold">{{ __('actions.save') }}</button>
</div>
