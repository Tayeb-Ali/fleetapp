<?php

declare(strict_types=1);

namespace App\Domain\Identity\Support;

use App\Domain\Identity\Services\SettingsService;
use Carbon\Carbon;

/**
 * Locale-aware, human-readable date output driven by company settings.
 *
 * Short formats are used in tables and reports, full formats in detail
 * screens, and timestamps keep the short date plus the time.
 */
final class DateDisplay
{
    /** @var array<string, string> format => translation key suffix */
    public const SHORT_FORMATS = [
        'd M Y' => 'short_compact',
        'd/m/Y' => 'short_numeric',
        'Y-m-d' => 'short_iso',
    ];

    /** @var array<string, string> format => translation key suffix */
    public const FULL_FORMATS = [
        'l j F Y' => 'full_weekday',
        'j F Y' => 'full_long',
        'l, j F Y' => 'full_weekday_comma',
    ];

    public const DEFAULT_SHORT = 'd M Y';

    public const DEFAULT_FULL = 'l j F Y';

    public static function short(mixed $value): string
    {
        return self::format($value, self::configured('short'));
    }

    public static function full(mixed $value): string
    {
        return self::format($value, self::configured('full'));
    }

    public static function datetime(mixed $value): string
    {
        return self::format($value, self::configured('short').' H:i');
    }

    /** Rendered sample used by the settings screen. */
    public static function sample(string $format): string
    {
        return self::format('2025-11-12', $format);
    }

    public static function sanitizeShort(?string $format): string
    {
        return self::sanitize($format, self::SHORT_FORMATS, self::DEFAULT_SHORT);
    }

    public static function sanitizeFull(?string $format): string
    {
        return self::sanitize($format, self::FULL_FORMATS, self::DEFAULT_FULL);
    }

    /** @param array<string, string> $allowed */
    private static function sanitize(?string $format, array $allowed, string $default): string
    {
        return $format !== null && array_key_exists($format, $allowed) ? $format : $default;
    }

    private static function configured(string $style): string
    {
        try {
            $settings = app(SettingsService::class);

            return $style === 'full'
                ? $settings->getDateFormatFull()
                : $settings->getDateFormatShort();
        } catch (\Throwable) {
            return $style === 'full' ? self::DEFAULT_FULL : self::DEFAULT_SHORT;
        }
    }

    private static function format(mixed $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->translatedFormat($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
