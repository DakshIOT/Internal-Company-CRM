<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportFilterRequest;
use App\Services\Reports\ReportFilterOptionsService;
use App\Services\Reports\VendorEntryReportQuery;
use App\Support\Reports\ReportModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VendorEntryReportController extends Controller
{
    public function index(ReportFilterRequest $request, VendorEntryReportQuery $reportQuery, ReportFilterOptionsService $optionsService): View
    {
        $filters = $request->filters();

        if (! $filters->hasEmployeeScope()) {
            return view('admin.reports.vendor-entries.index', [
                'filters' => $filters,
                'filterOptions' => $optionsService->forFilters($filters),
                'summary' => $this->emptySummary(),
                'entries' => $this->emptyPaginator($request),
                'vendorTotals' => collect(),
                'module' => ReportModule::VENDOR_ENTRIES,
            ]);
        }

        $summary = $reportQuery->summary($filters);

        return view('admin.reports.vendor-entries.index', [
            'filters' => $filters,
            'filterOptions' => $optionsService->forFilters($filters),
            'summary' => $summary,
            'entries' => $reportQuery->rows($filters),
            'vendorTotals' => $summary['vendor_totals'],
            'module' => ReportModule::VENDOR_ENTRIES,
        ]);
    }

    public function export(ReportFilterRequest $request, VendorEntryReportQuery $reportQuery): BinaryFileResponse|RedirectResponse
    {
        $filters = $request->filters();

        if (! $filters->hasEmployeeScope()) {
            return redirect()->route('admin.reports.vendor-entries.index', $filters->query());
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

        return ReportModule::filenamePrefix(ReportModule::VENDOR_ENTRIES).'-'.$from.'-to-'.$to.'.xlsx';
    }

    protected function emptySummary(): array
    {
        return [
            'entry_count' => 0,
            'amount_minor' => 0,
            'vendor_totals' => collect(),
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
