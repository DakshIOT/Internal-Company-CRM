<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkbookExport implements WithMultipleSheets
{
    public function __construct(protected array $sheets)
    {
    }

    public function sheets(): array
    {
        $workbookSheets = [];

        foreach ($this->sheets as $title => $rows) {
            $workbookSheets[] = new ArrayReportSheet($title, $rows);
        }

        return $workbookSheets;
    }
}
