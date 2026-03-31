@php
    use App\Support\Money;

    $pageGroups = $entries->getCollection()->groupBy(fn ($entry) => optional($entry->entry_date)->toDateString());
    $editRouteTemplate = route('employee.functions.edit', ['functionEntry' => '__ENTRY__']);
    $showFrozenFund = auth()->user()?->supportsFrozenFund();
    $actionColumnCount = $showFrozenFund ? 13 : 12;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="crm-section-title">Function Entry</p>
                <h1 class="mt-1 font-display text-2xl font-semibold text-slate-950 sm:text-3xl">Function Register</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Date-wise function totals for {{ $currentVenue->name }}, with quick actions opened from one simple modal.
                </p>
            </div>
            <div class="flex flex-wrap gap-2 crm-print-hidden">
                <span class="crm-chip bg-slate-950 text-white">{{ $currentVenue->name }}</span>
                <button type="button" onclick="window.print()" class="crm-button crm-button-secondary justify-center px-4 py-2.5">
                    Print current view
                </button>
                <a href="{{ route('employee.functions.create') }}" class="crm-button crm-button-primary justify-center px-4 py-2.5">
                    Add function
                </a>
            </div>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="{
            actionModalOpen: false,
            actionEntry: { id: null, name: '', date: '' },
            openActionModal(entry) { this.actionEntry = entry; this.actionModalOpen = true; },
            editUrl(tab) { return '{{ $editRouteTemplate }}'.replace('__ENTRY__', this.actionEntry.id) + '?tab=' + tab; },
            viewUrl() { return '{{ $editRouteTemplate }}'.replace('__ENTRY__', this.actionEntry.id); }
        }"
    >
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-{{ $showFrozenFund ? '5' : '4' }}">
            <article class="crm-kpi">
                <p class="crm-section-title">Entries in venue</p>
                <p class="mt-3 font-display text-3xl font-semibold text-slate-950">{{ $workspaceTotals['grand']['entry_count'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Grand function total</p>
                <p class="mt-3 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['function_total_minor']) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Grand paid</p>
                <p class="mt-3 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['paid_total_minor']) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Grand pending</p>
                <p class="mt-3 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['pending_total_minor']) }}</p>
            </article>
            @if ($showFrozenFund)
                <article class="crm-kpi">
                    <p class="crm-section-title">Net after frozen fund</p>
                    <p class="mt-3 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['net_total_after_frozen_fund_minor']) }}</p>
                </article>
            @endif
        </section>

        <section class="crm-panel p-4 sm:p-5 crm-print-hidden">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_12rem_auto]">
                <x-text-input name="search" :value="$filters['search'] ?? ''" class="crm-input w-full" placeholder="Search function name or notes" />
                <x-text-input name="entry_date" type="date" :value="$filters['entry_date'] ?? ''" class="crm-input w-full" />
                <div class="flex gap-2">
                    <button type="submit" class="crm-button crm-button-secondary w-full justify-center px-4 py-2.5">Filter</button>
                    <a href="{{ route('employee.functions.index') }}" class="crm-button crm-button-secondary w-full justify-center px-4 py-2.5">Reset</a>
                </div>
            </form>
        </section>

        <section class="crm-panel overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="crm-section-title">Function register</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-950">Date-wise table</h2>
                </div>
                <span class="text-sm text-slate-500">{{ $entries->total() }} total entries</span>
            </div>

            <div class="crm-table-wrap rounded-none border-0">
                <table class="crm-table min-w-[1350px]">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Notes</th>
                            <th>Packages</th>
                            <th>Extra Charges</th>
                            <th>Installments</th>
                            <th>Discounts</th>
                            <th>Files</th>
                            <th>Function Total</th>
                            <th>Paid</th>
                            <th>Pending</th>
                            @if ($showFrozenFund)
                                <th>Frozen Fund</th>
                            @endif
                            <th>Net Total</th>
                            <th class="crm-print-hidden">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pageGroups as $date => $dateEntries)
                            @php
                                $dateFunctionTotal = $dateEntries->sum('function_total_minor');
                                $datePaidTotal = $dateEntries->sum('paid_total_minor');
                                $datePendingTotal = $dateEntries->sum('pending_total_minor');
                                $dateFrozenFund = $dateEntries->sum('frozen_fund_minor');
                                $dateNetTotal = $dateEntries->sum('net_total_after_frozen_fund_minor');
                            @endphp

                            @foreach ($dateEntries as $entry)
                                <tr>
                                    <td>{{ optional($entry->entry_date)->format('d M Y') }}</td>
                                    <td class="font-semibold text-slate-950">{{ $entry->name }}</td>
                                    <td>{{ $entry->notes ?: 'No notes' }}</td>
                                    <td>{{ $entry->packages_count }}</td>
                                    <td>{{ $entry->extra_charges_count }}</td>
                                    <td>{{ $entry->installments_count }}</td>
                                    <td>{{ $entry->discounts_count }}</td>
                                    <td>{{ $entry->attachments_count }}</td>
                                    <td>{{ Money::formatMinor($entry->function_total_minor) }}</td>
                                    <td>{{ Money::formatMinor($entry->paid_total_minor) }}</td>
                                    <td>{{ Money::formatMinor($entry->pending_total_minor) }}</td>
                                    @if ($showFrozenFund)
                                        <td>{{ Money::formatMinor($entry->frozen_fund_minor) }}</td>
                                    @endif
                                    <td>{{ Money::formatMinor($entry->net_total_after_frozen_fund_minor) }}</td>
                                    <td class="crm-print-hidden">
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                class="crm-button crm-button-primary px-4 py-2"
                                                @click='openActionModal(@json(["id" => $entry->id, "name" => $entry->name, "date" => optional($entry->entry_date)->format("d M Y")]))'
                                            >
                                                Actions
                                            </button>
                                            <a href="{{ route('employee.functions.show', $entry) }}" class="crm-button crm-button-secondary px-4 py-2">View</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="bg-cyan-50/80">
                                <td colspan="8" class="font-semibold text-slate-900">
                                    Date Total {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                                </td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($dateFunctionTotal) }}</td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($datePaidTotal) }}</td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($datePendingTotal) }}</td>
                                @if ($showFrozenFund)
                                    <td class="font-semibold text-slate-950">{{ Money::formatMinor($dateFrozenFund) }}</td>
                                @endif
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($dateNetTotal) }}</td>
                                <td class="crm-print-hidden"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $actionColumnCount }}" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No function entries found for the current filter.
                                </td>
                            </tr>
                        @endforelse

                        @if ($pageGroups->isNotEmpty())
                            <tr class="bg-amber-50">
                                <td colspan="8" class="font-semibold text-slate-950">Grand Total</td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['function_total_minor']) }}</td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['paid_total_minor']) }}</td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['pending_total_minor']) }}</td>
                                @if ($showFrozenFund)
                                    <td class="font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['frozen_fund_minor']) }}</td>
                                @endif
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['net_total_after_frozen_fund_minor']) }}</td>
                                <td class="crm-print-hidden"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        {{ $entries->links() }}

        <div
            x-cloak
            x-show="actionModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm crm-print-hidden"
            @click.self="actionModalOpen = false"
            @keydown.escape.window="actionModalOpen = false"
        >
            <div class="max-h-[88vh] w-full max-w-xl overflow-y-auto rounded-[1.75rem] border border-white/20 bg-white p-6 shadow-2xl sm:p-7">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="crm-section-title">Function actions</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950" x-text="actionEntry.name"></h2>
                        <p class="mt-2 text-sm text-slate-500" x-text="actionEntry.date"></p>
                    </div>
                    <button type="button" class="rounded-full border border-slate-200 px-3 py-1 text-sm text-slate-500" @click="actionModalOpen = false">
                        Close
                    </button>
                </div>

                <div class="mt-6 grid gap-3">
                    <a :href="editUrl('packages')" class="crm-button crm-button-primary justify-center">Packages</a>
                    <a :href="editUrl('extra-charges')" class="crm-button crm-button-secondary justify-center">Extra Charges</a>
                    <a :href="editUrl('installments')" class="crm-button crm-button-secondary justify-center">Installments</a>
                    <a :href="editUrl('discounts')" class="crm-button crm-button-secondary justify-center">Discounts</a>
                    <a :href="viewUrl()" class="crm-button crm-button-secondary justify-center">Open full entry</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
