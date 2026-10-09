@props(['stage' => 'purchased'])

@php
    $colors = [
        'purchased' => '#94a3b8',
        'shipped' => '#8b5cf6',
        'arrived' => '#0ea5e9',
        'clearing' => '#f59e0b',
        'cleared' => '#f97316',
        'instock' => '#14b8a6',
        'sold' => '#ec4899',
        'delivered' => '#22c55e',
    ];
    $color = $colors[$stage] ?? '#4f46e5';
    $labelKey = 'stages.'.$stage;
    $label = __($labelKey) === $labelKey ? ucfirst(str_replace('_', ' ', (string) $stage)) : __($labelKey);
@endphp

<span class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full text-white whitespace-nowrap" style="background: {{ $color }}">{{ $label }}</span>
