@php
    use App\Support\Money;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">Daily Billing report</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Admin-only rows and totals from the Daily Billing ledger.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @include('admin.reports.partials.module-tabs', ['filters' => $filters, 'module' => $module])
        @include('admin.reports.partials.filter-card', [
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'module' => $module,
            'supportsVenue' => true,
            'resetRoute' => route('admin.reports.daily-billing.index'),
            'exportRoute' => 'admin.reports.daily-billing.export',
        ])

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="crm-kpi"><p class="crm-section-title">Total amount</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($summary['amount_minor']) }}</p></article>
            <article class="crm-kpi"><p class="crm-section-title">Row count</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $summary['entry_count'] }}</p></article>
            <article class="crm-kpi"><p class="crm-section-title">Venue scoped</p><p class="mt-4 text-sm leading-6 text-slate-600">Yes, driven by explicit admin filters.</p></article>
            <article class="crm-kpi"><p class="crm-section-title">Export</p><p class="mt-4 text-sm leading-6 text-slate-600">Summary and entry rows in one workbook.</p></article>
        </section>

        @include('admin.reports.partials.amount-report-table', ['entries' => $entries, 'supportsVenue' => true, 'showVendor' => false])

        {{ $entries->links() }}
    </div>
</x-app-layout>
