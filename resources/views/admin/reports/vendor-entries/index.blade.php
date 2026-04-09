@php
    use App\Support\Money;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="crm-toolbar">
            <div>
                <p class="crm-section-title">Admin Reports</p>
                <h1 class="font-display text-3xl font-semibold text-slate-950">Vendor Entry report</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Vendor rollups and detail rows from the vendor ledger.</p>
            </div>
            <a href="{{ route('admin.reports.vendor-entries.export', $filters->query()) }}" class="crm-button crm-button-secondary justify-center">
                Export current report
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @include('admin.reports.partials.module-tabs', ['filters' => $filters, 'module' => $module])
        @include('admin.reports.partials.employee-venue-scope', [
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'moduleRoute' => 'admin.reports.vendor-entries.index',
            'exportRoute' => 'admin.reports.vendor-entries.export',
        ])

        @if (! $filters->hasEmployeeScope())
            @include('admin.reports.partials.employee-scope-empty')
        @else
            <section class="crm-summary-grid">
                <article class="crm-kpi"><p class="crm-section-title">Total amount</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($summary['amount_minor']) }}</p></article>
                <article class="crm-kpi"><p class="crm-section-title">Row count</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $summary['entry_count'] }}</p></article>
                <article class="crm-kpi"><p class="crm-section-title">Vendor groups</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $vendorTotals->count() }}</p></article>
                <article class="crm-kpi"><p class="crm-section-title">Largest vendor total</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor((int) ($vendorTotals->first()['amount_minor'] ?? 0)) }}</p></article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-6">
                    @include('admin.reports.partials.amount-report-table', ['entries' => $entries, 'supportsVenue' => true, 'showVendor' => true])
                    {{ $entries->links() }}
                </div>

                <article class="crm-panel p-6">
                    <p class="crm-section-title">Vendor totals</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($vendorTotals as $row)
                            <div class="flex items-center justify-between gap-3 rounded-[1.25rem] border border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $row['vendor_name'] }}</p>
                                    <p class="text-slate-500">{{ $row['entry_count'] }} rows</p>
                                </div>
                                <span class="font-semibold text-slate-950">{{ Money::formatMinor($row['amount_minor']) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No vendor totals for the current scope.</p>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif
    </div>
</x-app-layout>
