<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportFilterRequest;
use App\Services\Reports\AdminDashboardMetricsService;
use App\Services\Reports\ReportFilterOptionsService;
use App\Support\Reports\ReportModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        ReportFilterRequest $request,
        AdminDashboardMetricsService $metricsService,
        ReportFilterOptionsService $optionsService
    ): View|RedirectResponse {
        $filters = $request->filters();

        if ($filters->module) {
            return redirect()->route(
                ReportModule::routeName($filters->module),
                collect($filters->query())->except('module')->all()
            );
        }

        $metrics = $metricsService->overview($filters);

        return view('admin.reports.dashboard', [
            'filters' => $filters,
            'filterOptions' => $optionsService->forFilters($filters),
            'metrics' => $metrics,
            'moduleLinks' => collect(ReportModule::all())->map(function (string $module) use ($filters) {
                return [
                    'label' => ReportModule::label($module),
                    'route' => ReportModule::routeName($module),
                    'query' => $filters->query(),
                ];
            })->all(),
        ]);
    }
}
