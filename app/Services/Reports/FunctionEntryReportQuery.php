<?php

namespace App\Services\Reports;

use App\Models\FunctionEntry;
use App\Models\FunctionPackage;
use App\Models\FunctionServiceLine;
use App\Reports\Filters\ReportFilters;
use App\Services\Reports\Concerns\AppliesReportFilters;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FunctionEntryReportQuery
{
    use AppliesReportFilters;

    protected bool $requiresUserSelection = true;

    public function summary(ReportFilters $filters): array
    {
        $summary = $this->filteredQuery($filters)
            ->selectRaw('COUNT(*) as entry_count')
            ->selectRaw('COALESCE(SUM(function_total_minor), 0) as function_total_minor')
            ->selectRaw('COALESCE(SUM(paid_total_minor), 0) as paid_total_minor')
            ->selectRaw('COALESCE(SUM(pending_total_minor), 0) as pending_total_minor')
            ->selectRaw('COALESCE(SUM(frozen_fund_minor), 0) as frozen_fund_minor')
            ->selectRaw('COALESCE(SUM(net_total_after_frozen_fund_minor), 0) as net_total_after_frozen_fund_minor')
            ->first();

        return [
            'entry_count' => (int) ($summary->entry_count ?? 0),
            'function_total_minor' => (int) ($summary->function_total_minor ?? 0),
            'paid_total_minor' => (int) ($summary->paid_total_minor ?? 0),
            'pending_total_minor' => (int) ($summary->pending_total_minor ?? 0),
            'frozen_fund_minor' => (int) ($summary->frozen_fund_minor ?? 0),
            'net_total_after_frozen_fund_minor' => (int) ($summary->net_total_after_frozen_fund_minor ?? 0),
        ];
    }

    public function rows(ReportFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with([
                'user:id,name,role',
                'venue:id,name,code',
                'attachments' => fn ($query) => $query
                    ->select(['id', 'attachable_id', 'attachable_type', 'original_name', 'mime_type', 'disk', 'storage_path'])
                    ->orderBy('id'),
            ])
            ->withCount(['attachments', 'packages'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function rowsForExport(ReportFilters $filters): Collection
    {
        return $this->filteredQuery($filters)
            ->with(['user:id,name,role', 'venue:id,name,code'])
            ->withCount(['attachments', 'packages'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (FunctionEntry $entry) {
                return [
                    optional($entry->entry_date)->toDateString(),
                    $entry->venue->name ?? '',
                    $entry->user->name ?? '',
                    $entry->user?->roleLabel() ?? '',
                    $entry->name,
                    Money::toDecimal($entry->package_total_minor),
                    Money::toDecimal($entry->extra_charge_total_minor),
                    Money::toDecimal($entry->discount_total_minor),
                    Money::toDecimal($entry->function_total_minor),
                    Money::toDecimal($entry->paid_total_minor),
                    Money::toDecimal($entry->pending_total_minor),
                    Money::toDecimal($entry->frozen_fund_minor),
                    Money::toDecimal($entry->net_total_after_frozen_fund_minor),
                    (int) $entry->packages_count,
                    (int) $entry->attachments_count,
                ];
            });
    }

    public function packageTotals(ReportFilters $filters): Collection
    {
        $query = FunctionPackage::query()
            ->join('function_entries', 'function_entries.id', '=', 'function_packages.function_entry_id')
            ->join('users', 'users.id', '=', 'function_entries.user_id')
            ->selectRaw('function_packages.package_id, function_packages.name_snapshot as package_name, COUNT(*) as entry_count, COALESCE(SUM(function_packages.total_minor), 0) as total_minor')
            ->groupBy('function_packages.package_id', 'function_packages.name_snapshot')
            ->orderBy('function_packages.name_snapshot');

        $this->applyFunctionEntryJoinFilters($query, $filters, $this->requiresUserSelection);

        if ($filters->packageId) {
            $query->where('function_packages.package_id', $filters->packageId);
        }

        if ($filters->serviceId) {
            $query->whereExists(function ($serviceQuery) use ($filters) {
                $serviceQuery->select(DB::raw(1))
                    ->from('function_service_lines')
                    ->whereColumn('function_service_lines.function_package_id', 'function_packages.id')
                    ->where('function_service_lines.service_id', $filters->serviceId)
                    ->where('function_service_lines.is_selected', true);
            });
        }

        return $query->get()->map(function ($row) {
            return [
                'package_id' => (int) $row->package_id,
                'package_name' => $row->package_name,
                'entry_count' => (int) $row->entry_count,
                'total_minor' => (int) $row->total_minor,
            ];
        });
    }

    public function serviceTotals(ReportFilters $filters): Collection
    {
        $query = FunctionServiceLine::query()
            ->join('function_packages', 'function_packages.id', '=', 'function_service_lines.function_package_id')
            ->join('function_entries', 'function_entries.id', '=', 'function_packages.function_entry_id')
            ->join('users', 'users.id', '=', 'function_entries.user_id')
            ->where('function_service_lines.is_selected', true)
            ->selectRaw('function_service_lines.service_id, function_service_lines.item_name_snapshot as service_name, COUNT(*) as line_count, COALESCE(SUM(function_service_lines.line_total_minor), 0) as total_minor')
            ->groupBy('function_service_lines.service_id', 'function_service_lines.item_name_snapshot')
            ->orderBy('function_service_lines.item_name_snapshot');

        $this->applyFunctionEntryJoinFilters($query, $filters, $this->requiresUserSelection);

        if ($filters->packageId) {
            $query->where('function_packages.package_id', $filters->packageId);
        }

        if ($filters->serviceId) {
            $query->where('function_service_lines.service_id', $filters->serviceId);
        }

        return $query->get()->map(function ($row) {
            return [
                'service_id' => (int) $row->service_id,
                'service_name' => $row->service_name,
                'line_count' => (int) $row->line_count,
                'total_minor' => (int) $row->total_minor,
            ];
        });
    }

    public function exportSheets(ReportFilters $filters): array
    {
        $summary = $this->summary($filters);

        return [
            'Summary' => [
                ['Filter', 'Value'],
                ['Date From', $filters->dateFrom ?? 'All'],
                ['Date To', $filters->dateTo ?? 'All'],
                ['Venue', $filters->venueId ?? 'All'],
                ['User', $filters->userId ?? 'All'],
                ['Employee Type', $filters->employeeRole ?? 'All'],
                ['Package', $filters->packageId ?? 'All'],
                ['Service', $filters->serviceId ?? 'All'],
                ['Search', $filters->search ?? 'None'],
                ['Record Count', $summary['entry_count']],
                ['Function Total', Money::toDecimal($summary['function_total_minor'])],
                ['Paid', Money::toDecimal($summary['paid_total_minor'])],
                ['Pending', Money::toDecimal($summary['pending_total_minor'])],
                ['Frozen Fund', Money::toDecimal($summary['frozen_fund_minor'])],
                ['Net Total After Frozen Fund', Money::toDecimal($summary['net_total_after_frozen_fund_minor'])],
            ],
            'Entries' => array_merge([[
                'Entry Date',
                'Venue',
                'Employee',
                'Employee Type',
                'Name',
                'Package Total',
                'Extra Charges',
                'Discounts',
                'Function Total',
                'Paid',
                'Pending',
                'Frozen Fund',
                'Net Total After Frozen Fund',
                'Packages Count',
                'Attachments Count',
            ]], $this->rowsForExport($filters)->all()),
            'Package Totals' => array_merge([['Package', 'Entry Count', 'Total']], $this->packageTotals($filters)->map(function (array $row) {
                return [$row['package_name'], $row['entry_count'], Money::toDecimal($row['total_minor'])];
            })->all()),
            'Service Totals' => array_merge([['Service', 'Line Count', 'Total']], $this->serviceTotals($filters)->map(function (array $row) {
                return [$row['service_name'], $row['line_count'], Money::toDecimal($row['total_minor'])];
            })->all()),
        ];
    }

    protected function filteredQuery(ReportFilters $filters): Builder
    {
        $query = FunctionEntry::query();

        $this->applySharedFilters($query, $filters, ['name', 'notes'], true, $this->requiresUserSelection);

        if ($filters->packageId) {
            $query->whereHas('packages', function (Builder $packageQuery) use ($filters) {
                $packageQuery->where('package_id', $filters->packageId);
            });
        }

        if ($filters->serviceId) {
            $query->whereHas('packages.serviceLines', function (Builder $serviceQuery) use ($filters) {
                $serviceQuery
                    ->where('service_id', $filters->serviceId)
                    ->where('is_selected', true);
            });
        }

        return $query;
    }
}
