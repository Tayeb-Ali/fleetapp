@props(['title' => null, 'open' => false])

<div x-data="{ open: @js($open) }" x-show="open" class="fixed inset-0 z-40" style="display: {{ $open ? 'block' : 'none' }};">
    <div class="absolute inset-0 bg-black/50" {{ $attributes->has('wire:model') ? '' : '' }}></div>
    <div class="absolute inset-x-0 bottom-0 mx-auto max-w-xl max-h-[90vh] overflow-auto bg-white dark:bg-slate-900 rounded-t-2xl shadow-2xl p-4">
        @if ($title)
            <div class="flex items-center justify-between gap-2 mb-2">
                <strong>{{ $title }}</strong>
                <button type="button" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800" onclick="this.closest('.fixed').style.display='none'">✕</button>
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
