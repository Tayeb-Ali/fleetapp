<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries\Concerns;

use App\Domain\Identity\Services\SettingsService;
use Carbon\Carbon;

/**
 * Localized labels + value formatting shared by report queries.
 *
 * Reports build their own rows/columns; this trait keeps the
 * translation keys and formatting rules (dates, money, stages,
 * issues) in one place so screens, PDF and XLSX stay consistent.
 */
trait LocalizesReport
{
    /** Localized report title (from reports.titles.{key}). */
    public function title(): string
    {
        return (string) __($this->titleKey());
    }

    /** Localized vehicle-field label (reuses vehicles.* keys). */
    protected function field(string $field): string
    {
        return (string) __('vehicles.'.$field);
    }

    /** Localized aggregate/report-only column label. */
    protected function column(string $key, array $replace = []): string
    {
        return (string) __('reports.labels.'.$key, $replace);
    }

    /** Localized stage label (stages.{value}); falls back to the raw value. */
    protected function stageLabel(?string $stage): string
    {
        if ($stage === null || $stage === '') {
            return '';
        }

        $key = 'stages.'.$stage;
        $label = __($key);

        return $label === $key ? $stage : (string) $label;
    }

    /** Localized data-quality issue label (reports.issues.{code}). */
    protected function issueLabel(string $code): string
    {
        $key = 'reports.issues.'.$code;
        $label = __($key);

        return $label === $key ? $code : (string) $label;
    }

    /** Locale-aware short date, e.g. "10 Oct 2026" / "10 أكتوبر 2026". */
    protected function formatDate(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->translatedFormat('d M Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    /** Money value with the configured currency, e.g. "30,000.00 USD". */
    protected function formatMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2, '.', ',').' '.$this->reportCurrency();
    }

    protected function reportCurrency(): string
    {
        try {
            return app(SettingsService::class)->getCurrency();
        } catch (\Throwable) {
            return (string) config('fleet.currency', SettingsService::DEFAULT_CURRENCY);
        }
    }
}
