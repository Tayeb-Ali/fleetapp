<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Services;

use App\Domain\Identity\Services\SettingsService;
use App\Domain\Reporting\Contracts\ReportQuery;
use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Renders ReportQuery tables to PDF via Barryvdh DomPDF.
 *
 * - A4 landscape when > 8 columns, else portrait (mirrors legacy pdf()).
 * - DejaVu Sans (bundled with dompdf) so Arabic text renders; the
 *   reports/pdf blade also sets font-family + UTF-8 meta.
 * - dompdf cannot shape Arabic script itself (letters would render
 *   disconnected and in the wrong order), so every Arabic string is
 *   pre-shaped with ar-php glyph joining (visual-order presentation
 *   forms) before rendering. Non-Arabic strings pass through untouched.
 */
class ReportPdfService
{
    private ?Arabic $arabic = null;

    public function render(ReportQuery $query): \Barryvdh\DomPDF\PDF
    {
        $columns = array_values($query->columns());
        $rows = array_map(
            fn ($row) => array_values((array) $row),
            $query->rows()
        );

        $orientation = count($columns) > 8 ? 'landscape' : 'portrait';

        // Every Arabic string is composed + pre-shaped here so the blade
        // never prints raw logical-order Arabic (dompdf would render it
        // disconnected). This covers title, headers, cells, meta, footer.
        return Pdf::loadView('reports.pdf', [
            'title' => $this->shape($query->title()),
            'columns' => array_map(
                fn ($c) => $this->shapeHeader((string) $c, count($columns) > 8),
                $columns
            ),
            'rows' => array_map(
                fn ($row) => array_map($this->shape(...), $row),
                $rows
            ),
            'meta' => $this->shape($this->metaLine($rows)),
            'footer' => $this->shape($this->companyName().' · '.$query->title()),
            'empty' => $this->shape(__('app.no_results')),
            'currency' => $this->currency(),
            'compact' => count($columns) > 8,
            'rtl' => app()->getLocale() === 'ar',
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

    /**
     * Pre-shape an Arabic string into visual-order glyph-joined
     * presentation forms for dompdf. Anything without Arabic letters
     * (Latin, numbers, dates in English, empty values) is returned as is.
     */
    private function shape(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || ! preg_match('/\p{Arabic}/u', $value)) {
            return $value;
        }

        try {
            // 500 = never wrap here; dompdf wraps table cells itself.
            // $hindo=false keeps Western digits per the app money convention
            // ("8,500.00 USD", counts) instead of Hindi digits.
            return $this->arabic()->utf8Glyphs($value, 500, false);
        } catch (\Throwable) {
            return $value;
        }
    }

    private function arabic(): Arabic
    {
        return $this->arabic ??= new Arabic;
    }

    private function metaLine(array $rows): string
    {
        return __('reports.generated').' '.now()->translatedFormat('d M Y H:i').' UTC | '.count($rows).' '.__('reports.records');
    }

    /**
     * Shape a header label. In wide (compact) tables a multi-word Arabic
     * header would wrap bottom-up once shaped, so stack it word-per-line
     * (each line shaped separately); the blade joins them with <br> in
     * reading order. Latin headers are left for dompdf to wrap (correct).
     */
    private function shapeHeader(string $value, bool $compact): string
    {
        if ($compact && preg_match('/\p{Arabic}/u', $value)) {
            $words = preg_split('/\s+/u', trim($value));
            if (is_array($words) && count($words) > 1) {
                $lines = [];
                foreach ($words as $word) {
                    if ($word === '/' && $lines !== []) {
                        $lines[count($lines) - 1] .= ' /';

                        continue;
                    }
                    $lines[] = $word;
                }

                return implode("\n", array_map($this->shape(...), $lines));
            }
        }

        return $this->shape($value);
    }

    private function companyName(): string
    {
        try {
            return app(SettingsService::class)->getCompany();
        } catch (\Throwable) {
            return (string) config('app.name', 'Fleet');
        }
    }

    private function currency(): string
    {
        try {
            return app(SettingsService::class)->getCurrency();
        } catch (\Throwable) {
            return SettingsService::DEFAULT_CURRENCY;
        }
    }
}
