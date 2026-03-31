<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportFilterRequest;
use App\Services\Reports\AdminIncomeReportQuery;
use App\Services\Reports\ReportFilterOptionsService;
use App\Support\Reports\ReportModule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminIncomeReportController extends Controller
{
    public function index(ReportFilterRequest $request, AdminIncomeReportQuery $reportQuery, ReportFilterOptionsService $optionsService): View
    {
        $filters = $request->filters();

        return view('admin.reports.admin-income.index', [
            'filters' => $filters,
            'filterOptions' => $optionsService->forFilters($filters),
            'summary' => $reportQuery->summary($filters),
            'entries' => $reportQuery->rows($filters),
            'module' => ReportModule::ADMIN_INCOME,
        ]);
    }

    public function export(ReportFilterRequest $request, AdminIncomeReportQuery $reportQuery): BinaryFileResponse
    {
        $filters = $request->filters();

        return Excel::download(
            new WorkbookExport($reportQuery->exportSheets($filters)),
            $this->filename($filters)
        );
    }

    protected function filename($filters): string
    {
        $from = $filters->dateFrom ?? 'all';
        $to = $filters->dateTo ?? 'all';

        return ReportModule::filenamePrefix(ReportModule::ADMIN_INCOME).'-'.$from.'-to-'.$to.'.xlsx';
    }
}
