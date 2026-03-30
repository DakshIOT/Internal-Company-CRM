<?php

namespace App\Services\Functions;

use App\Models\FunctionEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class FunctionEntryWorkspaceTotalsService
{
    public function forUserVenue(User $user, int $venueId, ?string $entryDate = null): array
    {
        $baseQuery = FunctionEntry::query()->forWorkspace($user, $venueId);

        return [
            'daily' => $entryDate
                ? $this->aggregate((clone $baseQuery)->whereDate('entry_date', $entryDate))
                : $this->emptySummary(),
            'grand' => $this->aggregate($baseQuery),
        ];
    }

    protected function aggregate(Builder $query): array
    {
        $totals = $query->selectRaw('COUNT(*) as entry_count')
            ->selectRaw('COALESCE(SUM(function_total_minor), 0) as function_total_minor')
            ->selectRaw('COALESCE(SUM(paid_total_minor), 0) as paid_total_minor')
            ->selectRaw('COALESCE(SUM(pending_total_minor), 0) as pending_total_minor')
            ->selectRaw('COALESCE(SUM(frozen_fund_minor), 0) as frozen_fund_minor')
            ->selectRaw('COALESCE(SUM(net_total_after_frozen_fund_minor), 0) as net_total_after_frozen_fund_minor')
            ->first();

        return [
            'entry_count' => (int) ($totals->entry_count ?? 0),
            'function_total_minor' => (int) ($totals->function_total_minor ?? 0),
            'paid_total_minor' => (int) ($totals->paid_total_minor ?? 0),
            'pending_total_minor' => (int) ($totals->pending_total_minor ?? 0),
            'frozen_fund_minor' => (int) ($totals->frozen_fund_minor ?? 0),
            'net_total_after_frozen_fund_minor' => (int) ($totals->net_total_after_frozen_fund_minor ?? 0),
        ];
    }

    protected function emptySummary(): array
    {
        return [
            'entry_count' => 0,
            'function_total_minor' => 0,
            'paid_total_minor' => 0,
            'pending_total_minor' => 0,
            'frozen_fund_minor' => 0,
            'net_total_after_frozen_fund_minor' => 0,
        ];
    }
}
