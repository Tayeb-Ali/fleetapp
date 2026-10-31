<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Domain\Reporting\ReportRegistry;
use App\Domain\Reporting\Support\ReportFilters;
use Livewire\Component;

/** Single report table with date filters, page size and download actions. */
class ReportShow extends Component
{
    private const PAGE_SIZES = ['10', '25', '50', '100', 'all'];

    private const DEFAULT_PER_PAGE = '25';

    public string $reportKey = '';

    /** '' = all dates. */
    public string $dateField = '';

    public string $from = '';

    public string $to = '';

    public string $perPage = self::DEFAULT_PER_PAGE;

    public int $page = 1;

    public bool $filtersOpen = true;

    public function mount(string $reportKey): void
    {
        $this->reportKey = $reportKey;
        $this->dateField = ReportFilters::defaultDateField($reportKey);
    }

    public function updated(string $name): void
    {
        if ($name === 'dateField' && $this->dateField === '') {
            $this->from = '';
            $this->to = '';
        }

        if (in_array($name, ['from', 'to', 'dateField', 'perPage'], true)) {
            $this->page = 1;
        }
    }

    public function applyPreset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ],
            'last_30' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            default => [null, null],
        };

        $this->from = $from?->toDateString() ?? '';
        $this->to = $to?->toDateString() ?? '';

        if ($preset !== 'all' && $this->dateField === '') {
            $this->dateField = ReportFilters::defaultDateField($this->reportKey) ?: 'purchased_at';
        }

        $this->page = 1;
    }

    public function resetFilters(): void
    {
        $this->dateField = ReportFilters::defaultDateField($this->reportKey);
        $this->from = '';
        $this->to = '';
        $this->perPage = self::DEFAULT_PER_PAGE;
        $this->page = 1;
    }

    public function toggleFilters(): void
    {
        $this->filtersOpen = ! $this->filtersOpen;
    }

    public function goto(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function render()
    {
        $query = ReportRegistry::find($this->reportKey);
        abort_if(! $query, 404, __('reports.unknown'));

        $filters = ReportFilters::make(
            $this->reportKey,
            $this->from,
            $this->to,
            $this->dateField,
            dateFieldGiven: true,
        );
        app()->instance(ReportFilters::class, $filters);

        $rows = $query->rows();
        $total = count($rows);

        $perPage = in_array($this->perPage, self::PAGE_SIZES, true) ? $this->perPage : 'all';
        $size = $perPage === 'all' ? max(1, $total) : (int) $perPage;
        $pages = max(1, (int) ceil($total / $size));
        $page = min(max(1, $this->page), $pages);
        $this->page = $page;

        return view('livewire.reports.report-show', [
            'report' => $query,
            'columns' => $query->columns(),
            'rows' => array_slice($rows, ($page - 1) * $size, $perPage === 'all' ? null : $size),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
            'first' => $total === 0 ? 0 : (($page - 1) * $size) + 1,
            'last' => min($total, $page * $size),
            'filters' => $filters,
            'dateFields' => ReportFilters::DATE_FIELDS,
            'exportXlsx' => $this->exportUrl('xlsx'),
            'exportPdf' => $this->exportUrl('pdf'),
        ]);
    }

    protected function exportUrl(string $format): string
    {
        $params = [
            'key' => $this->reportKey,
            'format' => $format,
        ];

        if ($this->from !== '' || $this->to !== '') {
            $params['from'] = $this->from;
            $params['to'] = $this->to;
            $params['date_field'] = $this->dateField;
        }

        return route('reports.export', $params);
    }
}
