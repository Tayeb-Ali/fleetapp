<div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
    @php $tKey = 'vehicle.transitions.'.$transitionKey; $tLabel = __($tKey) === $tKey ? ($def['label'] ?? $transitionKey) : __($tKey); @endphp
    <b>{{ $tLabel }}</b>
    <div class="mt-3 space-y-1">
        @foreach ((array) ($def['fields'] ?? ['remarks']) as $key)
            @php
                $meta = $labels[$key] ?? [$key, 'text'];
                $type = is_array($meta) ? ($meta[1] ?? 'text') : 'text';
                $isRequired = in_array($key, (array) ($def['required'] ?? []), true);
            @endphp
            <label class="block text-xs text-slate-500">{{ __('vehicles.'.$key) }}{{ $isRequired ? ' *' : '' }}
                @if ($type === 'area')
                    <textarea wire:model="data.{{ $key }}" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm"></textarea>
                @else
                    <input wire:model="data.{{ $key }}" type="{{ $type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text') }}" @if($type==='number') step="any" @endif class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                @endif
            </label>
            @error('data.'.$key)<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        @endforeach
        @error('data')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    </div>
    <button wire:click="save" class="mt-3 w-full px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold">{{ __('actions.save') }}</button>
</div>
