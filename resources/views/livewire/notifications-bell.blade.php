<div x-data="{ open: false }" @click.outside="open = false" wire:poll.60s class="relative">
    <button type="button" @click="open = ! open" class="relative grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-300 text-slate-500 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" title="{{ __('nav.notifications') }}" aria-label="{{ __('nav.notifications') }}" :aria-expanded="open">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        @if ($unread > 0)
            <span class="absolute -top-1 end-0 grid h-4 min-w-4 place-items-center rounded-full bg-red-600 px-1 text-[10px] font-semibold leading-none text-white" aria-label="{{ __('notifications.unread_count', ['count' => $unread]) }}">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition.duration.150ms class="fixed inset-x-3 top-28 z-50 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg dark:border-slate-800 dark:bg-slate-900 sm:absolute sm:inset-x-auto sm:end-0 sm:top-full sm:mt-2 sm:w-96">
        <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-3 py-2 dark:border-slate-800">
            <span class="text-sm font-semibold">{{ __('nav.notifications') }}</span>
            @if ($unread > 0)
                <button wire:click="markAllRead" class="text-xs font-semibold text-indigo-600 underline dark:text-indigo-400">{{ __('notifications.mark_all') }}</button>
            @endif
        </div>

        <ul class="max-h-96 divide-y divide-slate-100 overflow-auto dark:divide-slate-800">
            @forelse ($items as $item)
                @php
                    $accent = match ($item['category']) {
                        'vehicle' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300',
                        'user' => 'bg-teal-100 text-teal-600 dark:bg-teal-950 dark:text-teal-300',
                        default => 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-300',
                    };
                @endphp
                <li wire:key="bell-{{ $item['id'] }}" class="px-3 py-2.5 {{ $item['read'] ? '' : 'bg-indigo-50/50 dark:bg-indigo-950/20' }}">
                    <div class="flex gap-2.5">
                        <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full {{ $accent }}" aria-hidden="true">
                            @switch($item['category'])
                                @case('vehicle')
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h10v8H3z"/><path d="M13 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/></svg>
                                    @break
                                @case('user')
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                                    @break
                                @default
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>
                            @endswitch
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm {{ $item['read'] ? 'text-slate-600 dark:text-slate-300' : 'font-semibold' }}">
                                @if ($item['url'])
                                    <a href="{{ url($item['url']) }}" wire:click.prevent="open('{{ $item['id'] }}')" class="hover:underline">{{ $item['headline'] }}</a>
                                @else
                                    {{ $item['headline'] }}
                                @endif
                            </p>
                            @if ($item['vehicle'] || $item['vin'])
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    <bdi>{{ $item['vehicle'] ?? $item['vin'] }}</bdi>
                                    @if ($item['vin'])
                                        <span class="font-mono" dir="ltr">· {{ $item['vin'] }}</span>
                                    @endif
                                </p>
                            @endif
                            @if ($item['from'] || $item['to'])
                                <div class="mt-1 flex items-center gap-1.5">
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
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500" title="{{ $item['created_full'] }}">{{ $item['created_human'] }}</p>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('notifications.empty') }}</li>
            @endforelse
        </ul>

        <a href="{{ route('notifications.index') }}" class="block border-t border-slate-200 px-3 py-2 text-center text-xs font-semibold text-indigo-600 hover:bg-slate-50 dark:border-slate-800 dark:text-indigo-400 dark:hover:bg-slate-800/50">{{ __('notifications.view_all') }}</a>
    </div>
</div>
