<?php

namespace App\Services\Ledgers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LedgerWorkspaceTotalsService
{
    public function forEmployeeVenue(string $modelClass, User $user, int $venueId, ?string $selectedDate = null, int $dateLimit = 6): array
    {
        return $this->summarize(
            $modelClass::query()->forWorkspace($user, $venueId),
            $selectedDate,
            $dateLimit
        );
    }

    public function forAdmin(string $modelClass, ?string $selectedDate = null, int $dateLimit = 6): array
    {
        return $this->summarize($modelClass::query(), $selectedDate, $dateLimit);
    }

    public function summarize(Builder $query, ?string $selectedDate = null, int $dateLimit = 6): array
    {
        $grand = $this->aggregate(clone $query);
        $dateTotals = $this->dateTotals(clone $query, $dateLimit);
        $focus = $this->focusDateTotals(clone $query, $selectedDate, $dateTotals);

        return [
            'grand' => $grand,
            'focus' => $focus,
            'date_totals' => $dateTotals,
        ];
    }

    public function dateTotals(Builder $query, int $limit = 6): Collection
    {
        return $query
            ->selectRaw('entry_date, COUNT(*) as entry_count, COALESCE(SUM(amount_minor), 0) as amount_minor')
            ->groupBy('entry_date')
            ->orderByDesc('entry_date')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $date = $row->entry_date instanceof Carbon
                    ? $row->entry_date->toDateString()
                    : Carbon::parse($row->entry_date)->toDateString();

                return [
                    'entry_date' => $date,
                    'entry_count' => (int) $row->entry_count,
                    'amount_minor' => (int) $row->amount_minor,
                ];
            });
    }

    protected function aggregate(Builder $query): array
    {
        $summary = $query
            ->selectRaw('COUNT(*) as entry_count, COALESCE(SUM(amount_minor), 0) as amount_minor')
            ->first();

        return [
            'entry_count' => (int) ($summary?->entry_count ?? 0),
            'amount_minor' => (int) ($summary?->amount_minor ?? 0),
        ];
    }

    protected function focusDateTotals(Builder $query, ?string $selectedDate, Collection $dateTotals): array
    {
        if ($selectedDate) {
            $selected = $this->aggregate($query->whereDate('entry_date', $selectedDate));

            return array_merge($selected, [
                'label' => 'Selected date total',
                'entry_date' => $selectedDate,
            ]);
        }

        $latest = $dateTotals->first();

        return [
            'label' => 'Latest date total',
            'entry_date' => $latest['entry_date'] ?? null,
            'entry_count' => (int) ($latest['entry_count'] ?? 0),
            'amount_minor' => (int) ($latest['amount_minor'] ?? 0),
        ];
    }
}
