<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reports\ReportFilterRequest;
use App\Services\Reports\ReportFilterOptionsService;
use App\Support\Reports\ReportModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReportIndexController extends Controller
{
    public function __invoke(ReportFilterRequest $request, ReportFilterOptionsService $optionsService): View|RedirectResponse
    {
        $filters = $request->filters();

        if ($filters->module) {
            return redirect()->route(
                ReportModule::routeName($filters->module),
                collect($filters->query())->except('module')->all()
            );
        }

        return view('admin.reports.index', [
            'filters' => $filters,
            'filterOptions' => $optionsService->forFilters($filters),
            'reportLinks' => collect(ReportModule::all())->map(function (string $module) use ($filters) {
                return [
                    'label' => ReportModule::label($module),
                    'description' => 'Open filtered rows, totals, and export workbook.',
                    'route' => ReportModule::routeName($module),
                    'query' => $filters->query(),
                ];
            })->all(),
        ]);
    }
}
