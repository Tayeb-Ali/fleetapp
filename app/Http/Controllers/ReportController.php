<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reporting\Exports\GenericReportExport;
use App\Domain\Reporting\ReportRegistry;
use App\Domain\Reporting\Services\ReportPdfService;
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
        abort_if(! $query, 404, 'Unknown report');

        return view('reports.show', [
            'report' => $query,
            'columns' => $query->columns(),
            'rows' => $query->rows(),
        ]);
    }

    public function export(Request $request, string $key, ReportPdfService $pdfs): BinaryFileResponse|Response
    {
        $query = ReportRegistry::find($key);
        abort_if(! $query, 404, 'Unknown report');

        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'pdf') {
            return $pdfs->download($query);
        }

        if ($format === 'xlsx') {
            return Excel::download(
                GenericReportExport::forQuery($query),
                $query->key().'-'.date('Y-m-d').'.xlsx'
            );
        }

        abort(400, 'Unsupported format. Use ?format=xlsx or ?format=pdf.');
    }
}
