<?php

namespace App\Services\Reports;

use App\Models\VendorEntry;
use App\Reports\Filters\ReportFilters;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VendorEntryReportQuery extends AbstractAmountReportQuery
{
    protected string $modelClass = VendorEntry::class;
    protected array $searchColumns = ['name', 'notes', 'vendor_name_snapshot'];
    protected array $with = ['user:id,name,role', 'venue:id,name,code', 'venueVendor:id,name'];

    public function summary(ReportFilters $filters): array
    {
        $summary = parent::summary($filters);
        $summary['vendor_totals'] = $this->vendorTotals($filters);

        return $summary;
    }

    public function exportSheets(ReportFilters $filters): array
    {
        $summary = $this->summary($filters);

        return [
            'Summary' => $this->summarySheetRows($filters),
            'Entries' => array_merge([$this->exportHeadings()], $this->rowsForExport($filters)->all()),
            'Vendor Totals' => array_merge(
                [['Vendor', 'Entry Count', 'Amount']],
                $summary['vendor_totals']->map(function (array $row) {
                    return [$row['vendor_name'], $row['entry_count'], Money::toDecimal($row['amount_minor'])];
                })->all()
            ),
        ];
    }

    protected function filteredQuery(ReportFilters $filters): Builder
    {
        $query = parent::filteredQuery($filters);

        if ($filters->vendorId) {
            $query->where('venue_vendor_id', $filters->vendorId);
        }

        return $query;
    }

    protected function exportHeadings(): array
    {
        return ['Entry Date', 'Venue', 'Employee', 'Employee Type', 'Vendor', 'Name', 'Amount', 'Notes', 'Attachments Count'];
    }

    protected function mapExportRow($entry): array
    {
        return [
            optional($entry->entry_date)->toDateString(),
            $entry->venue->name ?? '',
            $entry->user->name ?? '',
            $entry->user?->roleLabel() ?? '',
            $entry->vendor_name_snapshot,
            $entry->name,
            Money::toDecimal($entry->amount_minor),
            (string) $entry->notes,
            (int) $entry->attachments_count,
        ];
    }

    protected function vendorTotals(ReportFilters $filters): Collection
    {
        return $this->filteredQuery($filters)
            ->selectRaw('vendor_name_snapshot, COUNT(*) as entry_count, COALESCE(SUM(amount_minor), 0) as amount_minor')
            ->groupBy('vendor_name_snapshot')
            ->orderBy('vendor_name_snapshot')
            ->get()
            ->map(function ($row) {
                return [
                    'vendor_name' => $row->vendor_name_snapshot,
                    'entry_count' => (int) $row->entry_count,
                    'amount_minor' => (int) $row->amount_minor,
                ];
            });
    }
}
