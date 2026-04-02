@php
    use App\Support\Money;
    use Illuminate\Contracts\Pagination\Paginator;

    $entryCollection = $entries instanceof Paginator ? collect($entries->items()) : collect($entries);
    $dateGroups = $entryCollection->groupBy(fn ($entry) => optional($entry->entry_date)->toDateString());
    $entryTotal = $entries instanceof Paginator ? $entries->total() : $entryCollection->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="crm-page-header">
            <div class="crm-page-heading">
                <p class="crm-section-title">Admin Income</p>
                <h1 class="crm-page-title">Admin Income Workspace</h1>
                <p class="crm-page-description">
                    Global admin-only income tracking with date totals, grand totals, and secured attachments.
                </p>
            </div>
            <div class="crm-page-header-actions">
                <span class="crm-chip bg-slate-950 text-white">Global Admin Context</span>
                <a href="{{ route('admin.admin-income.create') }}" class="crm-button crm-button-primary justify-center">Create entry</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="crm-kpi">
                <p class="crm-section-title">Entries in scope</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $workspaceTotals['grand']['entry_count'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Grand total</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['amount_minor']) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">{{ $workspaceTotals['focus']['label'] }}</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['focus']['amount_minor']) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Focus date</p>
                <p class="mt-4 text-lg font-semibold text-slate-950">
                    {{ $workspaceTotals['focus']['entry_date'] ? \Illuminate\Support\Carbon::parse($workspaceTotals['focus']['entry_date'])->format('d M Y') : 'No date yet' }}
                </p>
            </article>
        </section>

        <section class="crm-panel p-5">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_14rem_auto]">
                <x-text-input name="search" :value="$filters['search'] ?? ''" class="crm-input w-full" placeholder="Search by name or notes" />
                <x-text-input name="entry_date" type="date" :value="$filters['entry_date'] ?? ''" class="crm-input w-full" />
                <div class="flex gap-2">
                    <button type="submit" class="crm-button crm-button-secondary w-full justify-center">Filter</button>
                    <a href="{{ route('admin.admin-income.index') }}" class="crm-button crm-button-secondary w-full justify-center">Reset</a>
                </div>
            </form>
        </section>

        <section class="crm-panel overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="crm-section-title">Admin Income</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-950">Date-wise table</h2>
                </div>
                <span class="text-sm text-slate-500">{{ $entryTotal }} total entries</span>
            </div>

            <div class="crm-table-wrap rounded-none border-0">
                <table class="crm-table min-w-[960px]">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Notes</th>
                            <th>Files</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($dateGroups as $date => $dateEntries)
                            @php $dateTotal = $dateEntries->sum('amount_minor'); @endphp

                            @foreach ($dateEntries as $entry)
                                <tr>
                                    <td>{{ optional($entry->entry_date)->format('d M Y') }}</td>
                                    <td class="font-semibold text-slate-950">{{ $entry->name }}</td>
                                    <td>{{ $entry->notes ?: 'No notes' }}</td>
                                    <td>{{ $entry->attachments_count }}</td>
                                    <td>{{ Money::formatMinor($entry->amount_minor) }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('admin.admin-income.edit', $entry) }}" class="crm-button crm-button-primary px-4 py-2">Open entry</a>
                                            <a href="{{ route('admin.admin-income.edit', $entry) }}" class="crm-button crm-button-secondary px-4 py-2">View</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="bg-cyan-50/80">
                                <td colspan="4" class="font-semibold text-slate-900">
                                    Date Total {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                                </td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($dateTotal) }}</td>
                                <td></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No admin income entries found for the current filter.
                                </td>
                            </tr>
                        @endforelse

                        @if ($dateGroups->isNotEmpty())
                            <tr class="bg-amber-50">
                                <td colspan="4" class="font-semibold text-slate-950">Grand Total</td>
                                <td class="font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['amount_minor']) }}</td>
                                <td></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        @if ($entries->hasPages())
            {{ $entries->links() }}
        @endif
    </div>
</x-app-layout>
