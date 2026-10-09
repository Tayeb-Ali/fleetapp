<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Generic xlsx export for any ReportQuery (columns + rows). */
class GenericReportExport implements FromCollection, WithHeadings
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
}
