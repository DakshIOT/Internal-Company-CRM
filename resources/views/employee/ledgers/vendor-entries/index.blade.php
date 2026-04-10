@php
    use App\Support\Money;
    use Illuminate\Contracts\Pagination\Paginator;

    $entryCollection = $entries instanceof Paginator ? collect($entries->items()) : collect($entries);
    $pageGroups = $entryCollection->groupBy(fn ($entry) => optional($entry->entry_date)->toDateString());
    $entryTotal = $entries instanceof Paginator ? $entries->total() : $entryCollection->count();
    $renameVendorRouteTemplate = route('employee.vendor-entries.vendors.update', ['venueVendor' => '__VENDOR__']);
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
                <p class="crm-section-title">Vendor Entry</p>
                <h1 class="crm-page-title">{{ $isPrint ? 'Vendor Register Print View' : 'Vendor Register' }}</h1>
                <p class="crm-page-description">
                    {{ $isPrint ? 'Full filtered register prepared for printing.' : 'Track the four current-venue vendor slots, rename them when needed, and keep vendor-wise plus date-wise totals visible.' }}
                </p>
            </div>
            <div class="crm-page-header-actions crm-print-hidden">
                <span class="crm-chip bg-slate-950 text-white">{{ $currentVenue->name }}</span>
                <a href="{{ route('employee.vendor-entries.index', $printQuery) }}" target="_blank" class="crm-button crm-button-secondary justify-center px-4 py-2.5">Print full list</a>
                <a href="{{ route('employee.vendor-entries.export', $exportQuery) }}" class="crm-button crm-button-secondary justify-center px-4 py-2.5">Export Excel</a>
                <a href="{{ route('employee.vendor-entries.create') }}" class="crm-button crm-button-primary justify-center px-4 py-2.5">
                    Add entry
                </a>
            </div>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="{
            renameModalOpen: false,
            vendorId: null,
            vendorName: '',
            openRename(vendorId, vendorName) {
                this.vendorId = vendorId;
                this.vendorName = vendorName;
                this.renameModalOpen = true;
                this.$nextTick(() => this.$refs.renameInput && this.$refs.renameInput.focus());
            }
        }"
    >
        <section class="crm-summary-grid">
            <article class="crm-kpi">
                <p class="crm-section-title">Entries in scope</p>
                <p class="mt-3 font-display text-3xl font-semibold text-slate-950">{{ $workspaceTotals['grand']['entry_count'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Grand vendor total</p>
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

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($vendorTotals as $vendorTotal)
                <article class="crm-panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="crm-section-title">Vendor {{ $loop->iteration }}</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $vendorTotal['vendor_name'] }}</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ $vendorTotal['entry_count'] }} rows</p>
                        </div>
                        <button
                            type="button"
                            class="crm-button crm-button-secondary px-4 py-2 crm-print-hidden"
                            @click="openRename({{ $vendorTotal['venue_vendor_id'] }}, @js($vendorTotal['vendor_name']))"
                        >
                            Rename
                        </button>
                    </div>
                    <p class="mt-5 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($vendorTotal['amount_minor']) }}</p>
                </article>
            @endforeach
        </section>

        <section class="crm-panel p-4 sm:p-5 crm-print-hidden">
            <form method="GET" class="grid gap-3 lg:grid-cols-4">
                <x-text-input name="search" :value="$filters['search'] ?? ''" class="crm-input w-full" placeholder="Search name, notes, or vendor" />
                <x-text-input name="entry_date" type="date" :value="$filters['entry_date'] ?? ''" class="crm-input w-full" />
                <select name="venue_vendor_id" class="crm-input w-full">
                    <option value="">All vendors</option>
                    @foreach ($currentVenue->vendors as $vendorOption)
                        <option value="{{ $vendorOption->id }}" @selected(($filters['venue_vendor_id'] ?? null) == $vendorOption->id)>
                            {{ $vendorOption->name }} | Vendor {{ $vendorOption->slot_number }}
                        </option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="crm-button crm-button-secondary w-full justify-center px-4 py-2.5">Filter</button>
                    <a href="{{ route('employee.vendor-entries.index') }}" class="crm-button crm-button-secondary w-full justify-center px-4 py-2.5">Reset</a>
                </div>
            </form>
        </section>

        <section class="crm-panel overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="crm-section-title">Vendor register</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-950">Date-wise table</h2>
                </div>
                <span class="text-sm text-slate-500">{{ $entryTotal }} total entries</span>
            </div>

            <div class="crm-table-wrap rounded-none border-0">
                <table class="crm-table min-w-[1180px]">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vendor</th>
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
                                    <td>{{ $entry->vendor_name_snapshot ?: ($entry->venueVendor->name ?? 'No vendor') }}</td>
                                    <td class="font-semibold text-slate-950">{{ $entry->name }}</td>
                                    <td>{{ $entry->notes ?: 'No notes' }}</td>
                                    <td>{{ $entry->attachments_count }}</td>
                                    <td>{{ Money::formatMinor($entry->amount_minor) }}</td>
                                    <td class="crm-print-hidden">
                                        <a href="{{ route('employee.vendor-entries.edit', $entry) }}" class="crm-button crm-button-primary px-4 py-2">Open</a>
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="bg-cyan-50/80">
                                <td colspan="5" class="font-semibold text-slate-900">
                                    Date Total {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                                </td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($dateTotal) }}</td>
                                <td class="crm-print-hidden">
                                    <div class="flex justify-end">
                                        <a href="{{ route('employee.vendor-entries.print-date', ['entryDate' => $date]) }}" target="_blank" class="crm-button crm-button-secondary px-4 py-2">
                                            Print date
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No vendor entries found for the current filter.
                                </td>
                            </tr>
                        @endforelse

                        @if ($pageGroups->isNotEmpty())
                            <tr class="bg-amber-50">
                                <td colspan="5" class="font-semibold text-slate-950">Grand Total</td>
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

        <div
            x-cloak
            x-show="renameModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm crm-print-hidden"
            @click.self="renameModalOpen = false"
            @keydown.escape.window="renameModalOpen = false"
        >
            <div class="w-full max-w-lg rounded-[1.75rem] border border-white/20 bg-white p-6 shadow-2xl sm:p-7">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="crm-section-title">Rename vendor</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Current venue vendor slot</h2>
                    </div>
                    <button type="button" class="rounded-full border border-slate-200 px-3 py-1 text-sm text-slate-500" @click="renameModalOpen = false">
                        Close
                    </button>
                </div>

                <form method="POST" :action="'{{ $renameVendorRouteTemplate }}'.replace('__VENDOR__', vendorId)" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="vendor_name_modal" value="Vendor name" />
                        <x-text-input id="vendor_name_modal" x-ref="renameInput" name="name" x-model="vendorName" class="crm-input mt-2 w-full" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="crm-button crm-button-secondary px-4 py-2" @click="renameModalOpen = false">Cancel</button>
                        <button type="submit" class="crm-button crm-button-primary px-4 py-2">Save vendor name</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($isPrint)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</x-app-layout>
