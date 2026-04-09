<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportFilterRequest;
use App\Services\Reports\FunctionEntryReportQuery;
use App\Services\Reports\ReportFilterOptionsService;
use App\Support\Reports\ReportModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FunctionEntryReportController extends Controller
{
    public function index(
        ReportFilterRequest $request,
        FunctionEntryReportQuery $reportQuery,
        ReportFilterOptionsService $optionsService
    ): View {
        $filters = $request->filters();

        if (! $filters->hasEmployeeScope()) {
            return view('admin.reports.functions.index', [
                'filters' => $filters,
                'filterOptions' => $optionsService->forFilters($filters),
                'summary' => $this->emptySummary(),
                'entries' => $this->emptyPaginator($request),
                'packageTotals' => collect(),
                'serviceTotals' => collect(),
                'module' => ReportModule::FUNCTIONS,
            ]);
        }

        return view('admin.reports.functions.index', [
            'filters' => $filters,
            'filterOptions' => $optionsService->forFilters($filters),
            'summary' => $reportQuery->summary($filters),
            'entries' => $reportQuery->rows($filters),
            'packageTotals' => $reportQuery->packageTotals($filters),
            'serviceTotals' => $reportQuery->serviceTotals($filters),
            'module' => ReportModule::FUNCTIONS,
        ]);
    }

    public function export(ReportFilterRequest $request, FunctionEntryReportQuery $reportQuery): BinaryFileResponse|RedirectResponse
    {
        $filters = $request->filters();

        if (! $filters->hasEmployeeScope()) {
            return redirect()->route('admin.reports.functions.index', $filters->query());
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

        return ReportModule::filenamePrefix(ReportModule::FUNCTIONS).'-'.$from.'-to-'.$to.'.xlsx';
    }

    protected function emptySummary(): array
    {
        return [
            'entry_count' => 0,
            'function_total_minor' => 0,
            'paid_total_minor' => 0,
            'pending_total_minor' => 0,
            'frozen_fund_minor' => 0,
            'net_total_after_frozen_fund_minor' => 0,
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
