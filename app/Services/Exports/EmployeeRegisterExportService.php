<?php

namespace App\Services\Exports;

use App\Models\User;
use App\Models\Venue;
use App\Support\Money;
use Illuminate\Support\Collection;

class EmployeeRegisterExportService
{
    public function functionSheets(User $user, Venue $venue, array $filters, Collection $entries, array $summary, Collection $dateTotals): array
    {
        return [
            'Summary' => [
                ['Filter', 'Value'],
                ['Venue', $venue->name],
                ['Employee', $user->name],
                ['Employee Type', $user->roleLabel()],
                ['Search', $filters['search'] ?? 'None'],
                ['Entry Date', $filters['entry_date'] ?? 'All'],
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
                'Notes',
                'Packages Count',
                'Extra Charges Count',
                'Installments Count',
                'Discounts Count',
                'Attachments Count',
                'Function Total',
                'Paid',
                'Pending',
                'Frozen Fund',
                'Net Total After Frozen Fund',
            ]], $entries->map(function ($entry) use ($user, $venue) {
                return [
                    optional($entry->entry_date)->toDateString(),
                    $venue->name,
                    $user->name,
                    $user->roleLabel(),
                    $entry->name,
                    (string) $entry->notes,
                    (int) $entry->packages_count,
                    (int) $entry->extra_charges_count,
                    (int) $entry->installments_count,
                    (int) $entry->discounts_count,
                    (int) $entry->attachments_count,
                    Money::toDecimal($entry->function_total_minor),
                    Money::toDecimal($entry->paid_total_minor),
                    Money::toDecimal($entry->pending_total_minor),
                    Money::toDecimal($entry->frozen_fund_minor),
                    Money::toDecimal($entry->net_total_after_frozen_fund_minor),
                ];
            })->all()),
            'Date Totals' => array_merge([[
                'Entry Date',
                'Entry Count',
                'Function Total',
                'Paid',
                'Pending',
                'Frozen Fund',
                'Net Total After Frozen Fund',
            ]], $dateTotals->map(function (array $row) {
                return [
                    $row['entry_date'],
                    $row['entry_count'],
                    Money::toDecimal($row['function_total_minor']),
                    Money::toDecimal($row['paid_total_minor']),
                    Money::toDecimal($row['pending_total_minor']),
                    Money::toDecimal($row['frozen_fund_minor']),
                    Money::toDecimal($row['net_total_after_frozen_fund_minor']),
                ];
            })->all()),
        ];
    }

    public function amountSheets(
        User $user,
        Venue $venue,
        array $filters,
        Collection $entries,
        array $summary,
        Collection $dateTotals,
        string $moduleLabel,
        bool $includeVendors = false,
        ?Collection $vendorTotals = null
    ): array {
        $sheets = [
            'Summary' => [
                ['Filter', 'Value'],
                ['Module', $moduleLabel],
                ['Venue', $venue->name],
                ['Employee', $user->name],
                ['Employee Type', $user->roleLabel()],
                ['Search', $filters['search'] ?? 'None'],
                ['Entry Date', $filters['entry_date'] ?? 'All'],
                ['Vendor', $includeVendors ? ($filters['venue_vendor_id'] ?? 'All') : 'Not applicable'],
                ['Record Count', $summary['entry_count']],
                ['Amount', Money::toDecimal($summary['amount_minor'])],
            ],
            'Entries' => array_merge([[
                'Entry Date',
                'Venue',
                'Employee',
                'Employee Type',
                ...($includeVendors ? ['Vendor'] : []),
                'Name',
                'Notes',
                'Attachments Count',
                'Amount',
            ]], $entries->map(function ($entry) use ($includeVendors, $user, $venue) {
                return [
                    optional($entry->entry_date)->toDateString(),
                    $venue->name,
                    $user->name,
                    $user->roleLabel(),
                    ...($includeVendors ? [(string) $entry->vendor_name_snapshot] : []),
                    $entry->name,
                    (string) $entry->notes,
                    (int) $entry->attachments_count,
                    Money::toDecimal($entry->amount_minor),
                ];
            })->all()),
            'Date Totals' => array_merge([[
                'Entry Date',
                'Entry Count',
                'Amount',
            ]], $dateTotals->map(function (array $row) {
                return [
                    $row['entry_date'],
                    $row['entry_count'],
                    Money::toDecimal($row['amount_minor']),
                ];
            })->all()),
        ];

        if ($includeVendors) {
            $sheets['Vendor Totals'] = array_merge([[
                'Vendor',
                'Entry Count',
                'Amount',
            ]], ($vendorTotals ?? collect())->map(function (array $row) {
                return [
                    $row['vendor_name'],
                    $row['entry_count'],
                    Money::toDecimal($row['amount_minor']),
                ];
            })->all());
        }

        return $sheets;
    }
}
