<div>
    <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold">{{ __('notifications.title') }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('notifications.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if ($unread > 0)
                <span class="rounded-full bg-indigo-600 px-2.5 py-0.5 text-xs font-semibold text-white">{{ __('notifications.unread_count', ['count' => $unread]) }}</span>
                <button wire:click="markAllRead" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold dark:border-slate-700">{{ __('notifications.mark_all') }}</button>
            @endif
        </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-1.5">
        @foreach (['all', 'unread', 'vehicle', 'user', 'system'] as $key)
            <button wire:click="setFilter('{{ $key }}')"
                    class="rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $filter === $key ? 'bg-indigo-600 text-white' : 'border border-slate-200 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300' }}">
                {{ __('notifications.filters.'.$key) }}
            </button>
        @endforeach
    </div>

    @if (empty($items))
        <div class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-900">
            <p class="text-sm font-semibold">{{ __('notifications.empty') }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('notifications.empty_hint') }}</p>
        </div>
    @else
        <ul class="mt-3 space-y-2">
            @foreach ($items as $item)
                @php
                    $isUnread = ! $item['read'];
                    $accent = match ($item['category']) {
                        'vehicle' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300',
                        'user' => 'bg-teal-100 text-teal-600 dark:bg-teal-950 dark:text-teal-300',
                        default => 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-300',
                    };
                @endphp
                <li wire:key="notification-{{ $item['id'] }}" class="rounded-2xl border p-3 {{ $isUnread ? 'border-indigo-200 bg-indigo-50/60 dark:border-indigo-900 dark:bg-indigo-950/30' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' }}">
                    <div class="flex gap-3">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $accent }}" aria-hidden="true">
                            @switch($item['category'])
                                @case('vehicle')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h10v8H3z"/><path d="M13 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/></svg>
                                    @break
                                @case('user')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                                    @break
                                @default
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>
                            @endswitch
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold">
                                    @if ($item['url'])
                                        <a href="{{ url($item['url']) }}" wire:click.prevent="open('{{ $item['id'] }}')" class="hover:underline">{{ $item['headline'] }}</a>
                                    @else
                                        {{ $item['headline'] }}
                                    @endif
                                </p>
                                @if ($isUnread)
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-indigo-600" aria-label="{{ __('notifications.filters.unread') }}"></span>
                                @endif
                            </div>

                            @if ($item['vehicle'] || $item['vin'])
                                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    @if ($item['url'])
                                        <a href="{{ url($item['url']) }}" wire:click.prevent="open('{{ $item['id'] }}')" class="hover:underline"><bdi>{{ $item['vehicle'] ?? $item['vin'] }}</bdi></a>
                                    @else
                                        <bdi>{{ $item['vehicle'] ?? $item['vin'] }}</bdi>
                                    @endif
                                    @if ($item['vin'])
                                        <span class="font-mono" dir="ltr">· {{ $item['vin'] }}</span>
                                    @endif
                                </div>
                            @endif

                            @if ($item['from'] || $item['to'])
                                <div class="mt-1.5 flex items-center gap-1.5">
                                    @if ($item['from'])
                                        <x-stage-pill :stage="$item['from']" />
                                    @endif
                                    @if ($item['from'] && $item['to'])
                                        <span class="text-slate-400" aria-hidden="true">&rarr;</span>
                                    @endif
                                    @if ($item['to'])
                                        <x-stage-pill :stage="$item['to']" />
                                    @endif
                                </div>
                            @endif

                            @if ($item['details'])
                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $item['details'] }}</div>
                            @endif

                            @if ($item['note'])
                                <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ $item['note'] }}</p>
                            @endif

                            <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400 dark:text-slate-500">
                                <span title="{{ $item['created_full'] }}">{{ $item['created_human'] }}</span>
                                @if ($isUnread)
                                    <button wire:click="markRead('{{ $item['id'] }}')" class="underline">{{ __('notifications.mark_read') }}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-3">{{ $notifications?->links() }}</div>
    @endif
</div>
