@props([])

<div
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border border-slate-300 bg-white/80 p-0.5 text-xs font-semibold shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-900/80']) }}
    role="group"
    aria-label="{{ __('app.language') }}"
>
    <span class="grid h-7 w-7 shrink-0 place-items-center text-slate-400 dark:text-slate-500" aria-hidden="true">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18" />
            <path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18" />
        </svg>
    </span>

    @foreach (['ar', 'en'] as $code)
        @php($active = app()->getLocale() === $code)
        <a
            href="{{ route('lang.switch', $code) }}"
            hreflang="{{ $code }}"
            @if ($active) aria-current="true" @endif
            class="rounded-full px-2.5 py-1 leading-none whitespace-nowrap transition-colors {{ $active ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}"
        >{{ __('languages.'.$code) }}</a>
    @endforeach
</div>
