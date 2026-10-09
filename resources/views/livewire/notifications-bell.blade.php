<div wire:poll.60s class="relative">
    <button class="rounded px-2 py-1 text-sm" title="Notifications">
        🔔
        @if ($unread > 0)
            <span class="ml-1 rounded-full bg-red-600 px-1.5 text-xs text-white">{{ $unread }}</span>
        @endif
    </button>
    <div class="mt-1 w-80 rounded border bg-white shadow">
        <div class="flex items-center justify-between border-b px-3 py-2">
            <span class="text-sm font-medium">Notifications</span>
            <button wire:click="markAllRead" class="text-xs text-blue-600 underline">Mark all read</button>
        </div>
        <ul class="max-h-80 divide-y overflow-auto">
            @forelse ($notifications as $n)
                <li class="px-3 py-2 text-sm {{ $n->read_at ? 'text-gray-500' : 'font-medium' }}">
                    <p>{{ $n->data['action'] ?? class_basename($n->type) }} — {{ $n->data['vin'] ?? '' }}</p>
                    <p class="text-xs">{{ $n->data['stage_labels']['en'] ?? '' }} · {{ $n->created_at }}</p>
                    @if (! $n->read_at)
                        <button wire:click="markRead('{{ $n->id }}')" class="text-xs text-blue-600 underline">Mark read</button>
                    @endif
                </li>
            @empty
                <li class="px-3 py-2 text-sm text-gray-500">No notifications.</li>
            @endforelse
        </ul>
    </div>
</div>
