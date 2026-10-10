@props(['stage' => 'purchased'])

@php
    // Callers may pass a VehicleStage enum (Eloquent model) or a plain string.
    $stage = $stage instanceof \BackedEnum ? $stage->value : (string) $stage;
    $fallbackColors = [
        'purchased' => '#94a3b8',
        'shipped' => '#8b5cf6',
        'arrived' => '#0ea5e9',
        'clearing' => '#f59e0b',
        'cleared' => '#f97316',
        'instock' => '#14b8a6',
        'sold' => '#ec4899',
        'delivered' => '#22c55e',
    ];
    $configuredColors = config('fleet.stage_colors', $fallbackColors);
    $colors = is_array($configuredColors) ? array_merge($fallbackColors, $configuredColors) : $fallbackColors;
    $color = $colors[$stage] ?? '#4f46e5';
    $labelKey = 'stages.'.$stage;
    $label = __($labelKey) === $labelKey ? ucfirst(str_replace('_', ' ', (string) $stage)) : __($labelKey);
    $textColor = in_array($stage, ['purchased', 'clearing', 'instock', 'delivered'], true) ? '#1e293b' : '#ffffff';
@endphp

<span class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full whitespace-nowrap" style="background: {{ $color }}; color: {{ $textColor }}">{{ $label }}</span>
