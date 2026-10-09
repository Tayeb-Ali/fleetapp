<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Services;

use App\Domain\Reporting\Contracts\ReportQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Renders ReportQuery tables to PDF via Barryvdh DomPDF.
 *
 * - A4 landscape when > 8 columns, else portrait (mirrors legacy pdf()).
 * - DejaVu Sans (bundled with dompdf) so Arabic text renders; the
 *   reports/pdf blade also sets font-family + UTF-8 meta.
 */
class ReportPdfService
{
    public function render(ReportQuery $query): \Barryvdh\DomPDF\PDF
    {
        $columns = $query->columns();
        $orientation = count($columns) > 8 ? 'landscape' : 'portrait';

        return Pdf::loadView('reports.pdf', [
            'title' => $query->titleKey(),
            'columns' => $columns,
            'rows' => $query->rows(),
            'generated' => now()->format('Y-m-d H:i').' UTC',
        ])
            ->setPaper('a4', $orientation)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);
    }

    public function download(ReportQuery $query, ?string $filename = null): Response
    {
        return $this->render($query)->download($filename ?? $query->key().'-'.date('Y-m-d').'.pdf');
    }

    public function stream(ReportQuery $query): Response
    {
        return $this->render($query)->stream($query->key().'.pdf');
    }
}
