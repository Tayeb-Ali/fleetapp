<div>
    <div class="my-3">
        <input wire:model.live.debounce.300ms="search" placeholder="{{ __('actions.search') }}"
               class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
    </div>

    <div class="flex gap-1.5 overflow-x-auto my-2">
        <button wire:click="setStage('')"
                class="text-xs px-3 py-1.5 rounded-full border whitespace-nowrap {{ $stage === '' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700' }}">{{ __('actions.all') }}</button>
        @foreach ($stages as $key => $label)
            <button wire:click="setStage('{{ $key }}')"
                    class="text-xs px-3 py-1.5 rounded-full border whitespace-nowrap {{ $stage === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700' }}">{{ __('stages.'.$key) }}</button>
        @endforeach
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400 my-2">{{ $vehicles->total() }} {{ __('nav.vehicles') }}</p>

    <div class="space-y-2">
        @forelse ($vehicles as $v)
            @php $vin = is_array($v) ? ($v['vin'] ?? '') : ($v->vin ?? ''); @endphp
            <a href="{{ Route::has('vehicles.show') ? route('vehicles.show', $vin) : '#' }}"
               class="block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3">
                <div class="flex justify-between gap-2 items-start">
                    <b><bdi>{{ is_array($v) ? ($v['brand'] ?? '').' '.($v['model'] ?? '') : $v->brand.' '.$v->model }}</bdi></b>
                    <x-stage-pill :stage="is_array($v) ? ($v['stage'] ?? '') : $v->stage" />
                </div>
                <div class="font-mono text-xs text-slate-500 dark:text-slate-400 text-start" dir="ltr">{{ $vin }}</div>
                <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400 mt-1">
                    @php
                        $arr = is_array($v) ? $v : $v->toArray();
                        $bl = $arr['bl'] ?? '';
                        $client = $arr['client'] ?? $arr['buyer_name'] ?? '';
                        $loc = $arr['loc'] ?? $arr['warehouse_location'] ?? '';
                    @endphp
                    @if ($bl)<span>{{ __('vehicles.bl') }}: <b class="text-slate-900 dark:text-slate-100 font-medium"><bdi>{{ $bl }}</bdi></b></span>@endif
                    @if ($client)<span>{{ __('vehicles.client') }}: <b class="text-slate-900 dark:text-slate-100 font-medium"><bdi>{{ $client }}</bdi></b></span>@endif
                    @if ($loc)<span>{{ __('vehicles.loc') }}: <b class="text-slate-900 dark:text-slate-100 font-medium"><bdi>{{ $loc }}</bdi></b></span>@endif
                </div>
            </a>
        @empty
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_results') }}</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $vehicles->links() }}
    </div>
</div>
