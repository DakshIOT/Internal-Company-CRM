<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportFilterRequest;
use App\Services\Reports\DailyIncomeReportQuery;
use App\Services\Reports\ReportFilterOptionsService;
use App\Support\Reports\ReportModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyIncomeReportController extends Controller
{
    public function index(ReportFilterRequest $request, DailyIncomeReportQuery $reportQuery, ReportFilterOptionsService $optionsService): View
    {
        $filters = $request->filters();

        if (! $filters->hasEmployeeScope()) {
            return view('admin.reports.daily-income.index', [
                'filters' => $filters,
                'filterOptions' => $optionsService->forFilters($filters),
                'summary' => $this->emptySummary(),
                'entries' => $this->emptyPaginator($request),
                'module' => ReportModule::DAILY_INCOME,
            ]);
        }

        return view('admin.reports.daily-income.index', [
            'filters' => $filters,
            'filterOptions' => $optionsService->forFilters($filters),
            'summary' => $reportQuery->summary($filters),
            'entries' => $reportQuery->rows($filters),
            'module' => ReportModule::DAILY_INCOME,
        ]);
    }

    public function export(ReportFilterRequest $request, DailyIncomeReportQuery $reportQuery): BinaryFileResponse|RedirectResponse
    {
        $filters = $request->filters();

        if (! $filters->hasEmployeeScope()) {
            return redirect()->route('admin.reports.daily-income.index', $filters->query());
        }

        return Excel::download(
            new WorkbookExport($reportQuery->exportSheets($filters)),
            $this->filename($filters)
        );
    }

    protected function filename($filters): string
    {
        $from = $filters->dateFrom ?? 'all';
        $to = $filters->dateTo ?? 'all';

        return ReportModule::filenamePrefix(ReportModule::DAILY_INCOME).'-'.$from.'-to-'.$to.'.xlsx';
    }

    protected function emptySummary(): array
    {
        return [
            'entry_count' => 0,
            'amount_minor' => 0,
        ];
    }

    protected function emptyPaginator(ReportFilterRequest $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 15, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }
}
