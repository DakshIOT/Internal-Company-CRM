<?php

namespace App\Services\Reports;

use App\Models\DailyBillingEntry;
use App\Support\Money;

class DailyBillingReportQuery extends AbstractAmountReportQuery
{
    protected string $modelClass = DailyBillingEntry::class;

    protected function exportHeadings(): array
    {
        return ['Entry Date', 'Venue', 'Employee', 'Employee Type', 'Name', 'Amount', 'Notes', 'Attachments Count'];
    }

    protected function mapExportRow($entry): array
    {
        return [
            optional($entry->entry_date)->toDateString(),
            $entry->venue->name ?? '',
            $entry->user->name ?? '',
            $entry->user?->roleLabel() ?? '',
            $entry->name,
            Money::toDecimal($entry->amount_minor),
            (string) $entry->notes,
            (int) $entry->attachments_count,
        ];
    }
}
