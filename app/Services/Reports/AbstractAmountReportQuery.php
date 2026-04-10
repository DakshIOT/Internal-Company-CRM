<?php

namespace App\Services\Reports;

use App\Reports\Filters\ReportFilters;
use App\Support\Attachments\AttachmentExportFormatter;
use App\Services\Reports\Concerns\AppliesReportFilters;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class AbstractAmountReportQuery
{
    use AppliesReportFilters;

    protected string $modelClass;
    protected bool $supportsVenue = true;
    protected bool $requiresUserSelection = false;
    protected array $searchColumns = ['name', 'notes'];
    protected array $with = ['user:id,name,role', 'venue:id,name,code'];

    public function summary(ReportFilters $filters): array
    {
        $summary = $this->filteredQuery($filters)
            ->selectRaw('COUNT(*) as entry_count')
            ->selectRaw('COALESCE(SUM(amount_minor), 0) as amount_minor')
            ->first();

        return [
            'entry_count' => (int) ($summary->entry_count ?? 0),
            'amount_minor' => (int) ($summary->amount_minor ?? 0),
        ];
    }

    public function rows(ReportFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->rowQuery($filters)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function rowsForExport(ReportFilters $filters): Collection
    {
        return $this->rowQuery($filters)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($entry) => $this->mapExportRow($entry));
    }

    public function exportSheets(ReportFilters $filters): array
    {
        return [
            'Summary' => $this->summarySheetRows($filters),
            'Entries' => array_merge([$this->exportHeadings()], $this->rowsForExport($filters)->all()),
        ];
    }

    protected function rowQuery(ReportFilters $filters): Builder
    {
        $attachmentColumns = [
            'id',
            'attachable_id',
            'attachable_type',
            'original_name',
            'mime_type',
            'disk',
            'storage_path',
        ];

        return $this->filteredQuery($filters)
            ->with(array_merge($this->with, [
                'attachments' => fn ($query) => $query->select($attachmentColumns)->orderBy('id'),
            ]))
            ->withCount('attachments');
    }

    protected function filteredQuery(ReportFilters $filters): Builder
    {
        /** @var Builder $query */
        $query = ($this->modelClass)::query();

        return $this->applySharedFilters(
            $query,
            $filters,
            $this->searchColumns,
            $this->supportsVenue,
            $this->requiresUserSelection
        );
    }

    protected function summarySheetRows(ReportFilters $filters): array
    {
        $summary = $this->summary($filters);

        return [
            ['Filter', 'Value'],
            ['Date From', $filters->dateFrom ?? 'All'],
            ['Date To', $filters->dateTo ?? 'All'],
            ['Venue', $this->supportsVenue ? ($filters->venueId ?? 'All') : 'Not applicable'],
            ['User', $filters->userId ?? 'All'],
            ['Employee Type', $filters->employeeRole ?? 'All'],
            ['Search', $filters->search ?? 'None'],
            ['Record Count', $summary['entry_count']],
            ['Amount', Money::toDecimal($summary['amount_minor'])],
        ];
    }

    protected function attachmentNames($entry): string
    {
        return AttachmentExportFormatter::names($entry->attachments ?? collect());
    }

    protected function attachmentDownloadUrls($entry): string
    {
        return AttachmentExportFormatter::urls(
            $entry->attachments ?? collect(),
            fn ($attachment) => route('admin.reports.attachments.download', $attachment)
        );
    }

    abstract protected function exportHeadings(): array;

    abstract protected function mapExportRow($entry): array;
}
