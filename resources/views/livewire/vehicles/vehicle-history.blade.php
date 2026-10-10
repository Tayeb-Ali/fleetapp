<div>
    <ol class="relative border-s border-slate-200 dark:border-slate-800">
        @forelse ($items as $e)
            <li class="mb-4 ms-4">
                <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full bg-indigo-600"></div>
                <p class="text-sm font-medium">{{ $e->action ?? $e->note ?? 'Activity' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $e->created_at ?? $e->ts ?? '' }}
                    @if (! empty($e->uname ?? $e->actor ?? null)) · {{ $e->uname ?? $e->actor }} @endif
                </p>
                @if (! empty($e->note))
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $e->note }}</p>
                @endif
            </li>
        @empty
            <li class="ms-4 text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_results') }}</li>
        @endforelse
    </ol>
</div>
