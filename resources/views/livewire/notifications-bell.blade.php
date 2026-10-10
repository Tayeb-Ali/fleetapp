<div wire:poll.60s class="relative">
    <button class="rounded px-2 py-1 text-sm" title="{{ __('nav.notifications') }}" aria-label="{{ __('nav.notifications') }}">
        🔔
        @if ($unread > 0)
            <span class="ms-1 rounded-full bg-red-600 px-1.5 text-xs text-white">{{ $unread }}</span>
        @endif
    </button>
    <div class="mt-1 w-80 rounded border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 px-3 py-2">
            <span class="text-sm font-medium">{{ __('nav.notifications') }}</span>
            <button wire:click="markAllRead" class="text-xs text-indigo-600 dark:text-indigo-400 underline">{{ __('notifications.mark_all') }}</button>
        </div>
        <ul class="max-h-80 divide-y divide-slate-200 dark:divide-slate-800 overflow-auto">
            @forelse ($notifications as $n)
                <li class="px-3 py-2 text-sm {{ $n->read_at ? 'text-slate-500 dark:text-slate-400' : 'font-medium' }}">
                    <p>{{ $n->data['action'] ?? class_basename($n->type) }} — {{ $n->data['vin'] ?? '' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $n->data['stage_labels']['en'] ?? '' }} · {{ $n->created_at }}</p>
                    @if (! $n->read_at)
                        <button wire:click="markRead('{{ $n->id }}')" class="text-xs text-indigo-600 dark:text-indigo-400 underline">{{ __('notifications.mark_read') }}</button>
                    @endif
                </li>
            @empty
                <li class="px-3 py-2 text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_results') }}</li>
            @endforelse
        </ul>
    </div>
</div>
