<div>
    <ol class="relative border-s border-slate-200 dark:border-slate-800">
        @forelse ($items as $entry)
            @php
                $action = \App\Domain\Vehicle\Support\ActivityPresenter::action((string) ($entry->action ?? ''));
                $note = \App\Domain\Vehicle\Support\ActivityPresenter::note($entry->note ?? null);
                $date = \App\Domain\Vehicle\Support\ActivityPresenter::datetime($entry->created_at ?? $entry->ts ?? '');
            @endphp
            <li class="mb-4 ms-4">
                <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full bg-indigo-600"></div>
                <p class="text-sm font-medium">{{ $action ?: ($note ?? 'Activity') }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    <bdi>{{ $date }}</bdi>
                    @if (! empty($entry->uname ?? $entry->actor ?? null)) · {{ $entry->uname ?? $entry->actor }} @endif
                </p>
                @if (! empty($note))
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $note }}</p>
                @endif
            </li>
        @empty
            <li class="ms-4 text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_results') }}</li>
        @endforelse
    </ol>
</div>