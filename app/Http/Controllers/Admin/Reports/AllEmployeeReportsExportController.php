<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportFilterRequest;
use App\Services\Reports\DailyBillingReportQuery;
use App\Services\Reports\DailyIncomeReportQuery;
use App\Services\Reports\FunctionEntryReportQuery;
use App\Services\Reports\VendorEntryReportQuery;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AllEmployeeReportsExportController extends Controller
{
    public function __invoke(
        ReportFilterRequest $request,
        FunctionEntryReportQuery $functionReports,
        DailyIncomeReportQuery $dailyIncomeReports,
        DailyBillingReportQuery $dailyBillingReports,
        VendorEntryReportQuery $vendorReports
    ): BinaryFileResponse|RedirectResponse {
        $filters = $request->filters();

        if (! $filters->hasEmployeeScope()) {
            return redirect()->route('admin.reports.index', $filters->query());
        }

        $sheets = array_merge(
            $this->prefixSheets('Function', $functionReports->exportSheets($filters)),
            $this->prefixSheets('Daily Income', $dailyIncomeReports->exportSheets($filters)),
            $this->prefixSheets('Daily Billing', $dailyBillingReports->exportSheets($filters)),
            $this->prefixSheets('Vendor Entry', $vendorReports->exportSheets($filters))
        );

        return Excel::download(
            new WorkbookExport($sheets),
            $this->filename($filters->userId, $filters->dateFrom, $filters->dateTo)
        );
    }

    private function prefixSheets(string $prefix, array $sheets): array
    {
        $prefixed = [];

        foreach ($sheets as $title => $rows) {
            $prefixed[$prefix.' '.$title] = $rows;
        }

        return $prefixed;
    }

    private function filename(int $userId, ?string $dateFrom, ?string $dateTo): string
    {
        $from = $dateFrom ?? 'all';
        $to = $dateTo ?? 'all';

        return 'employee-'.$userId.'-all-reports-'.$from.'-to-'.$to.'.xlsx';
    }
}

