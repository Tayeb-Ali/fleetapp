<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Support;

/**
 * Date-range filter for report queries.
 *
 * Bound once per request (screen render or export) and applied inside the
 * shared FetchesVehicles trait, so the table, PDF and XLSX always contain
 * exactly the same rows.
 */
final class ReportFilters
{
    /** Canonical vehicle columns that can be used for date filtering. */
    public const DATE_FIELDS = [
        'purchased_at',
        'shipped_at',
        'arrived_at',
        'customs_date',
        'warehouse_in_at',
        'sold_at',
        'delivered_at',
    ];

    /** Sensible default date column per report ('' = no date filter). */
    private const DEFAULTS = [
        'summary' => '',
        'stock' => '',
        'available' => '',
        'sales' => 'sold_at',
        'shipments' => 'shipped_at',
        'customs' => 'customs_date',
        'full' => 'purchased_at',
        'profit' => 'sold_at',
        'outstanding' => 'sold_at',
        'costing' => 'purchased_at',
        'quality' => '',
    ];

    public function __construct(
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly string $dateField = '',
    ) {}

    public static function defaultDateField(string $reportKey): string
    {
        return self::DEFAULTS[$reportKey] ?? '';
    }

    /**
     * @param  bool  $dateFieldGiven  true keeps an explicit empty choice
     *                                ("all dates") instead of applying the
     *                                report default.
     */
    public static function make(
        string $reportKey,
        ?string $from,
        ?string $to,
        ?string $dateField,
        bool $dateFieldGiven = false,
    ): self {
        $field = $dateFieldGiven
            ? (in_array($dateField, self::DATE_FIELDS, true) ? (string) $dateField : '')
            : self::defaultDateField($reportKey);

        return new self(
            self::validDate($from),
            self::validDate($to),
            $field,
        );
    }

    public function isActive(): bool
    {
        return $this->dateField !== '' && ($this->from !== null || $this->to !== null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $vehicles
     * @return array<int, array<string, mixed>>
     */
    public function apply(array $vehicles): array
    {
        if (! $this->isActive()) {
            return $vehicles;
        }

        return array_values(array_filter($vehicles, fn (array $v) => $this->matches($v)));
    }

    /** @param  array<string, mixed>  $vehicle */
    public function matches(array $vehicle): bool
    {
        $date = self::dateOf($vehicle[$this->dateField] ?? null);

        if ($date === null) {
            return false;
        }

        if ($this->from !== null && $date < $this->from) {
            return false;
        }

        return ! ($this->to !== null && $date > $this->to);
    }

    /** Human-readable period for the PDF header; '' when inactive. */
    public function describe(): string
    {
        if (! $this->isActive()) {
            return '';
        }

        $field = (string) __('reports.date_fields.'.$this->dateField);

        if ($this->from !== null && $this->to !== null) {
            return (string) __('reports.filters.range', [
                'field' => $field,
                'from' => $this->from,
                'to' => $this->to,
            ]);
        }

        if ($this->from !== null) {
            return (string) __('reports.filters.range_from', [
                'field' => $field,
                'from' => $this->from,
            ]);
        }

        return (string) __('reports.filters.range_to', [
            'field' => $field,
            'to' => $this->to,
        ]);
    }

    /** Filename prefix that records the applied range, e.g. "2026-01-01_2026-01-31-". */
    public function filenameSuffix(): string
    {
        $parts = array_values(array_filter([$this->from, $this->to], fn ($v) => $v !== null));

        return $parts === [] ? '' : implode('_', $parts).'-';
    }

    /** Y-m-d date of a model/array value (date string, datetime or Carbon). */
    public static function dateOf(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $text, $matches) === 1) {
            return $matches[0];
        }

        $ts = strtotime($text);

        return $ts === false ? null : date('Y-m-d', $ts);
    }

    private static function validDate(?string $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }
}
