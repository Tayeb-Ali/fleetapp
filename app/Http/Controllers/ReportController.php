<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reporting\Exports\GenericReportExport;
use App\Domain\Reporting\ReportRegistry;
use App\Domain\Reporting\Services\ReportPdfService;
use App\Domain\Reporting\Support\ReportFilters;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Browsable reports + xlsx/pdf exports (legacy /api/reports + ?format=).
 */
class ReportController extends Controller
{
    public function index()
    {
        $reports = ReportRegistry::all();

        return view('reports.index', ['reports' => $reports]);
    }

    public function show(string $key)
    {
        $query = ReportRegistry::find($key);
        abort_if(! $query, 404, __('reports.unknown'));

        return view('reports.show', [
            'report' => $query,
            'columns' => $query->columns(),
            'rows' => $query->rows(),
        ]);
    }

    public function export(Request $request, string $key, ReportPdfService $pdfs): BinaryFileResponse|Response
    {
        $query = ReportRegistry::find($key);
        abort_if(! $query, 404, __('reports.unknown'));

        $validated = $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ]);

        $dateField = $request->query('date_field');
        $dateField = is_string($dateField) ? $dateField : null;

        $filters = ReportFilters::make(
            $key,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
            $dateField,
            dateFieldGiven: $request->query->has('date_field'),
        );
        app()->instance(ReportFilters::class, $filters);

        $stamp = ($filters->isActive() ? $filters->filenameSuffix() : '').date('Y-m-d');
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'pdf') {
            return $pdfs->download($query, $query->key().'-'.$stamp.'.pdf');
        }

        if ($format === 'xlsx') {
            return Excel::download(
                GenericReportExport::forQuery($query),
                $query->key().'-'.$stamp.'.xlsx'
            );
        }

        abort(400, __('reports.bad_format'));
    }
}
