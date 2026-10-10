<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Generic xlsx export for any ReportQuery (localized columns + rows). */
class GenericReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @param array<int,string> $headings @param array<int,array<int,mixed>> $rows */
    public function __construct(
        protected array $headings,
        protected array $rows,
    ) {}

    public static function forQuery(object $query): self
    {
        return new self($query->columns(), $query->rows());
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function collection(): Collection
    {
        return new Collection($this->rows);
    }

    public function title(): string
    {
        return 'Report';
    }

    public function styles(Worksheet $sheet): ?array
    {
        // Bold heading row + RTL sheet direction for Arabic exports.
        if (app()->getLocale() === 'ar') {
            $sheet->setRightToLeft(true);
        }

        return [1 => ['font' => ['bold' => true]]];
    }
}
