<?php

namespace App\Services\Ledgers;

use App\Models\User;
use App\Models\VendorEntry;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VendorEntryWorkspaceTotalsService extends LedgerWorkspaceTotalsService
{
    public function summarize(Builder $query, ?string $selectedDate = null, int $dateLimit = 6): array
    {
        $summary = parent::summarize($query, $selectedDate, $dateLimit);
        $summary['vendor_totals'] = $this->vendorTotals(clone $query);

        return $summary;
    }

    public function vendorTotalsForUserVenue(User $user, Venue $venue): Collection
    {
        $rows = VendorEntry::query()
            ->forWorkspace($user, (int) $venue->getKey())
            ->selectRaw('venue_vendor_id, COUNT(*) as entry_count, COALESCE(SUM(amount_minor), 0) as amount_minor')
            ->groupBy('venue_vendor_id')
            ->get()
            ->keyBy('venue_vendor_id');

        return $venue->vendors->map(function ($vendor) use ($rows) {
            $row = $rows->get($vendor->getKey());

            return [
                'venue_vendor_id' => (int) $vendor->getKey(),
                'vendor_name' => $vendor->name,
                'entry_count' => (int) ($row->entry_count ?? 0),
                'amount_minor' => (int) ($row->amount_minor ?? 0),
            ];
            });
    }

    public function vendorTotalsFromQuery(Builder $query): Collection
    {
        return $this->vendorTotals($query);
    }

    protected function vendorTotals(Builder $query): Collection
    {
        $table = (new VendorEntry())->getTable();

        return $query
            ->join('venue_vendors', 'venue_vendors.id', '=', $table.'.venue_vendor_id')
            ->selectRaw('venue_vendors.id as venue_vendor_id, venue_vendors.name as vendor_name, COUNT(*) as entry_count, COALESCE(SUM('.$table.'.amount_minor), 0) as amount_minor')
            ->groupBy('venue_vendors.id', 'venue_vendors.name')
            ->orderBy('venue_vendors.name')
            ->get()
            ->map(function ($row) {
                return [
                    'venue_vendor_id' => (int) $row->venue_vendor_id,
                    'vendor_name' => $row->vendor_name,
                    'entry_count' => (int) $row->entry_count,
                    'amount_minor' => (int) $row->amount_minor,
                ];
            });
    }
}
