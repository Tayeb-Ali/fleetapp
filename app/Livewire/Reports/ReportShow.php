<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Domain\Reporting\ReportRegistry;
use Livewire\Component;

/** Single report table with download + print actions. */
class ReportShow extends Component
{
    public string $reportKey = '';

    public function mount(string $reportKey): void
    {
        $this->reportKey = $reportKey;
    }

    public function render()
    {
        $query = ReportRegistry::find($this->reportKey);
        abort_if(! $query, 404, __('reports.unknown'));

        return view('livewire.reports.report-show', [
            'report' => $query,
            'columns' => $query->columns(),
            'rows' => $query->rows(),
        ]);
    }
}
