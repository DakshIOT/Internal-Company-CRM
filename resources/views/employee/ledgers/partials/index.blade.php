@php
    use App\Support\Money;
    use Illuminate\Contracts\Pagination\Paginator;

    $entryCollection = $entries instanceof Paginator ? collect($entries->items()) : collect($entries);
    $pageGroups = $entryCollection->groupBy(fn ($entry) => optional($entry->entry_date)->toDateString());
    $entryTotal = $entries instanceof Paginator ? $entries->total() : $entryCollection->count();
    $showVendors = ! empty($vendorOptions) && count($vendorOptions);
    $colspan = $showVendors ? 7 : 6;
    $printDateRoute = $printDateRoute ?? null;
    $printQuery = array_filter([
        'search' => $filters['search'] ?? null,
        'entry_date' => $filters['entry_date'] ?? null,
        'venue_vendor_id' => $filters['venue_vendor_id'] ?? null,
        'print' => 1,
    ], fn ($value) => ! is_null($value) && $value !== '');
    $exportQuery = array_filter([
        'search' => $filters['search'] ?? null,
        'entry_date' => $filters['entry_date'] ?? null,
        'venue_vendor_id' => $filters['venue_vendor_id'] ?? null,
    ], fn ($value) => ! is_null($value) && $value !== '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="crm-page-header">
            <div class="crm-page-heading">
                <p class="crm-section-title">{{ $moduleLabel }}</p>
                <h1 class="crm-page-title">{{ $isPrint ? $moduleLabel.' Print View' : $moduleLabel }}</h1>
                <p class="crm-page-description">{{ $isPrint ? 'Full filtered register prepared for printing.' : $moduleDescription }}</p>
            </div>
            <div class="crm-page-header-actions crm-print-hidden">
                <span class="crm-chip bg-slate-950 text-white">{{ $currentVenue->name }}</span>
                <a href="{{ route($indexRoute, $printQuery) }}" target="_blank" class="crm-button crm-button-secondary justify-center px-4 py-2.5">Print full list</a>
                <a href="{{ route(str_replace('.index', '.export', $indexRoute), $exportQuery) }}" class="crm-button crm-button-secondary justify-center px-4 py-2.5">Export Excel</a>
                <a href="{{ route($createRoute) }}" class="crm-button crm-button-primary justify-center px-4 py-2.5">Add entry</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="crm-kpi">
                <p class="crm-section-title">Entries in scope</p>
                <p class="mt-3 font-display text-3xl font-semibold text-slate-950">{{ $workspaceTotals['grand']['entry_count'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Grand total</p>
                <p class="mt-3 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['amount_minor']) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">{{ $workspaceTotals['focus']['label'] }}</p>
                <p class="mt-3 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['focus']['amount_minor']) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Focus date</p>
                <p class="mt-3 text-lg font-semibold text-slate-950">
                    {{ $workspaceTotals['focus']['entry_date'] ? \Illuminate\Support\Carbon::parse($workspaceTotals['focus']['entry_date'])->format('d M Y') : 'No date yet' }}
                </p>
            </article>
        </section>

        @if (! empty($workspaceTotals['vendor_totals']) && count($workspaceTotals['vendor_totals']) > 0)
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($workspaceTotals['vendor_totals'] as $vendorTotal)
                    <article class="crm-panel p-5">
                        <p class="crm-section-title">{{ $vendorTotal['vendor_name'] }}</p>
                        <p class="mt-3 text-2xl font-semibold text-slate-950">{{ Money::formatMinor($vendorTotal['amount_minor']) }}</p>
                        <p class="mt-2 text-sm text-slate-500">{{ $vendorTotal['entry_count'] }} rows</p>
                    </article>
                @endforeach
            </section>
        @endif

        <section class="crm-panel p-4 sm:p-5 crm-print-hidden">
            <form method="GET" class="grid gap-3 {{ $showVendors ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
                <x-text-input name="search" :value="$filters['search'] ?? ''" class="crm-input w-full" placeholder="Search name or notes" />
                <x-text-input name="entry_date" type="date" :value="$filters['entry_date'] ?? ''" class="crm-input w-full" />
                @if ($showVendors)
                    <select name="venue_vendor_id" class="crm-input w-full">
                        <option value="">All vendors</option>
                        @foreach ($vendorOptions as $vendorOption)
                            <option value="{{ $vendorOption->id }}" @selected(($filters['venue_vendor_id'] ?? null) == $vendorOption->id)>
                                {{ $vendorOption->name }} | Slot {{ $vendorOption->slot_number }}
                            </option>
                        @endforeach
                    </select>
                @endif
                <div class="flex gap-2">
                    <button type="submit" class="crm-button crm-button-secondary w-full justify-center px-4 py-2.5">Filter</button>
                    <a href="{{ route($indexRoute) }}" class="crm-button crm-button-secondary w-full justify-center px-4 py-2.5">Reset</a>
                </div>
            </form>
        </section>

        <section class="crm-panel overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="crm-section-title">{{ $moduleLabel }}</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-950">Date-wise table</h2>
                </div>
                <span class="text-sm text-slate-500">{{ $entryTotal }} total entries</span>
            </div>

            <div class="crm-table-wrap rounded-none border-0">
                <table class="crm-table min-w-[1100px]">
                    <thead>
                        <tr>
                            <th>Date</th>
                            @if ($showVendors)
                                <th>Vendor</th>
                            @endif
                            <th>Name</th>
                            <th>Notes</th>
                            <th>Files</th>
                            <th>Amount</th>
                            <th class="crm-print-hidden">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pageGroups as $date => $dateEntries)
                            @php $dateTotal = $dateEntries->sum('amount_minor'); @endphp

                            @foreach ($dateEntries as $entry)
                                <tr>
                                    <td>{{ optional($entry->entry_date)->format('d M Y') }}</td>
                                    @if ($showVendors)
                                        <td>{{ $entry->vendor_name_snapshot ?: ($entry->venueVendor->name ?? 'No vendor') }}</td>
                                    @endif
                                    <td class="font-semibold text-slate-950">{{ $entry->name }}</td>
                                    <td>{{ $entry->notes ?: 'No notes' }}</td>
                                    <td>{{ $entry->attachments_count }}</td>
                                    <td>{{ Money::formatMinor($entry->amount_minor) }}</td>
                                    <td class="crm-print-hidden">
                                        <a href="{{ route($editRoute, $entry) }}" class="crm-button crm-button-primary px-4 py-2">Open</a>
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="bg-cyan-50/80">
                                <td colspan="{{ $showVendors ? 5 : 4 }}" class="font-semibold text-slate-900">
                                    Date Total {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                                </td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($dateTotal) }}</td>
                                <td class="crm-print-hidden">
                                    @if ($printDateRoute)
                                        <div class="flex justify-end">
                                            <a href="{{ route($printDateRoute, ['entryDate' => $date]) }}" target="_blank" class="crm-button crm-button-secondary px-4 py-2">
                                                Print date
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $colspan }}" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No entries found for the current filter.
                                </td>
                            </tr>
                        @endforelse

                        @if ($pageGroups->isNotEmpty())
                            <tr class="bg-amber-50">
                                <td colspan="{{ $showVendors ? 5 : 4 }}" class="font-semibold text-slate-950">Grand Total</td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['amount_minor']) }}</td>
                                <td class="crm-print-hidden"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        @if ($entries instanceof Paginator && $entries->hasPages())
            <div class="crm-print-hidden">
                {{ $entries->links() }}
            </div>
        @endif
    </div>

    @if ($isPrint)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</x-app-layout>
