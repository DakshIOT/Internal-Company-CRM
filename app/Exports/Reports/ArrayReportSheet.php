<?php

namespace App\Exports\Reports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ArrayReportSheet implements FromArray, WithTitle
{
    public function __construct(
        protected string $title,
        protected array $rows
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return Str::limit($this->title, 31, '');
    }
}
