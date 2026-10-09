<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Domain\Reporting\ReportRegistry;
use Livewire\Component;

/** Cards list of all available reports. */
class ReportIndex extends Component
{
    public function render()
    {
        return view('livewire.reports.report-index', [
            'reports' => ReportRegistry::all(),
        ]);
    }
}
