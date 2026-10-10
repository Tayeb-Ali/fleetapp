<div>
    @if (count($tasks) > 0)
        <h2 class="text-xs text-slate-500 dark:text-slate-400 mt-4 mb-2">{{ __('app.tasks_waiting') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            @foreach ($tasks as $task)
                @php $from = $task['from'][0] ?? 'purchased'; @endphp
                <a href="{{ route('vehicles.index', ['stage' => $from]) }}"
                   class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 border-s-4"
                   style="border-inline-start-color: {{ $colors[$from] ?? '#4f46e5' }}">
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $task['label'] }}</span>
                    <b class="block text-2xl">{{ $task['count'] }}</b>
                </a>
            @endforeach
        </div>
    @endif

    <h2 class="text-xs text-slate-500 dark:text-slate-400 mt-4 mb-2">{{ __('app.stages_title') }} ({{ __('app.total') }} {{ $total }})</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        @foreach ($stages as $key => $label)
            <a href="{{ route('vehicles.index', ['stage' => $key]) }}"
               class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 border-s-4"
               style="border-inline-start-color: {{ $colors[$key] ?? '#4f46e5' }}">
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ __($label === $label ? 'stages.'.$key : $label) === 'stages.'.$key ? $label : __('stages.'.$key) }}</span>
                <b class="block text-2xl">{{ $counts[$key] ?? 0 }}</b>
            </a>
        @endforeach
    </div>
</div>
