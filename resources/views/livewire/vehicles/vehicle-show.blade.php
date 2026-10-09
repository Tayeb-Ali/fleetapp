<div>
    <div class="flex justify-between gap-2 items-start">
        <div>
            <b class="text-lg"><bdi>{{ $v['brand'] ?? '' }} {{ $v['model'] ?? '' }}</bdi></b>
            <div class="font-mono text-xs text-slate-500" dir="ltr">{{ $v['vin'] ?? '' }}</div>
        </div>
        <x-stage-pill :stage="$v['stage'] ?? 'purchased'" />
    </div>

    <div class="flex flex-wrap gap-2 mt-3">
        @foreach ($transitions as $key => $t)
            <a href="{{ route('vehicles.show', $v['vin']) }}?transition={{ $key }}"
               class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold">{{ $t['label'] ?? $key }}</a>
        @endforeach
        @if ((auth()->user()->role ?? '') === 'admin')
            <button wire:click="delete" onclick="return confirm('{{ __('actions.confirm_delete') }}')"
                    class="px-4 py-2.5 rounded-xl bg-red-600 text-white text-sm font-semibold">{{ __('actions.delete') }}</button>
        @endif
    </div>

    @if (request()->query('transition'))
        <div class="mt-3">
            <livewire:vehicles.transition-form :vin="$v['vin']" :transition-key="request()->query('transition')" :key="'tr-'.$v['vin'].'-'.request()->query('transition')" />
        </div>
    @endif

    <h2 class="text-xs text-slate-500 mt-5 mb-2">Vehicle history</h2>
    <div class="space-y-2 border-s-2 border-indigo-200 ps-3">
        @forelse ($events as $e)
            <div>
                <b class="text-sm">{{ $e['action'] ?? '' }}</b>
                <div class="text-xs text-slate-500">{{ $e['ts'] ?? $e['created_at'] ?? '' }} · {{ $e['uname'] ?? $e['user_name'] ?? '' }}</div>
                @if (! empty($e['note']))<div class="text-xs text-slate-500">{{ $e['note'] }}</div>@endif
            </div>
        @empty
            <p class="text-xs text-slate-500">—</p>
        @endforelse
    </div>

    <h2 class="text-xs text-slate-500 mt-5 mb-2">Vehicle details</h2>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl divide-y divide-slate-100 dark:divide-slate-800">
        @foreach ($fieldLabels as $key => $meta)
            @if (! in_array($key, ['stage', 'brand', 'model', 'vin']) && ! empty($v[$key]))
                <div class="flex justify-between gap-3 px-3 py-2 text-sm">
                    <span class="text-slate-500">{{ __('vehicles.'.$key) }}</span>
                    <b class="font-medium text-end"><bdi>{{ $v[$key] }}</bdi></b>
                </div>
            @endif
        @endforeach
    </div>

    <h2 class="text-xs text-slate-500 mt-5 mb-2">Documents and attachments</h2>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 space-y-2">
        @if (! empty($v['ord_no']))
            <div><a class="text-sm underline" href="{{ route('documents.sale-order', $v['vin']) }}">Sale order (PDF)</a></div>
        @endif
        @if (($v['stage'] ?? '') === 'delivered')
            <div><a class="text-sm underline" href="{{ route('documents.delivery-note', $v['vin']) }}">Delivery note (PDF)</a></div>
        @endif
        @forelse ($files as $f)
            <div class="flex justify-between text-sm border-t border-slate-100 dark:border-slate-800 pt-2">
                <span><bdi>{{ $f['name'] ?? '' }}</bdi><br><small class="text-slate-500">{{ $f['uname'] ?? '' }} · {{ $f['ts'] ?? '' }}</small></span>
            </div>
        @empty
            <p class="text-xs text-slate-500">—</p>
        @endforelse
    </div>
</div>
