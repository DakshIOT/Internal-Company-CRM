<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportFilterRequest;
use App\Reports\Filters\ReportFilters;
use App\Models\User;
use App\Services\Reports\AdminDashboardMetricsService;
use App\Services\Reports\ReportFilterOptionsService;
use App\Support\Role;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        ReportFilterRequest $request,
        AdminDashboardMetricsService $metricsService,
        ReportFilterOptionsService $optionsService
    ): View {
        $filters = $request->filters();
        $metrics = $metricsService->overview(ReportFilters::fromArray([]));
        $employeeCount = User::query()->whereIn('role', Role::employeeRoles())->count();

        return view('admin.reports.dashboard', [
            'filters' => $filters,
            'filterOptions' => $optionsService->forFilters($filters),
            'metrics' => $metrics,
            'employeeCount' => $employeeCount,
        ]);
    }
}
