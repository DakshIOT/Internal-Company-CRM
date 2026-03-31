<?php

namespace App\Services\Reports;

use App\Models\AdminIncomeEntry;
use App\Support\Money;

class AdminIncomeReportQuery extends AbstractAmountReportQuery
{
    protected string $modelClass = AdminIncomeEntry::class;
    protected bool $supportsVenue = false;
    protected array $with = ['user:id,name,role'];

    protected function exportHeadings(): array
    {
        return ['Entry Date', 'Created By', 'Created By Role', 'Name', 'Amount', 'Notes', 'Attachments Count'];
    }

    protected function mapExportRow($entry): array
    {
        return [
            optional($entry->entry_date)->toDateString(),
            $entry->user->name ?? '',
            $entry->user?->roleLabel() ?? '',
            $entry->name,
            Money::toDecimal($entry->amount_minor),
            (string) $entry->notes,
            (int) $entry->attachments_count,
        ];
    }
}
