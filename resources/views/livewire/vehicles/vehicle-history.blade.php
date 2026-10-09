<div>
    <ol class="relative border-s border-gray-200">
        @forelse ($items as $e)
            <li class="mb-4 ms-4">
                <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full bg-blue-600"></div>
                <p class="text-sm font-medium">{{ $e->action ?? $e->note ?? 'Activity' }}</p>
                <p class="text-xs text-gray-500">
                    {{ $e->created_at ?? $e->ts ?? '' }}
                    @if (! empty($e->uname ?? $e->actor ?? null)) · {{ $e->uname ?? $e->actor }} @endif
                </p>
                @if (! empty($e->note))
                    <p class="mt-1 text-sm text-gray-700">{{ $e->note }}</p>
                @endif
            </li>
        @empty
            <li class="ms-4 text-sm text-gray-500">No history yet.</li>
        @endforelse
    </ol>
</div>
