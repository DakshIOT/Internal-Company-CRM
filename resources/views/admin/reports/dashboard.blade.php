@php
    use App\Support\Money;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="crm-toolbar">
            <div>
                <p class="crm-section-title">Admin Reports</p>
                <h1 class="font-display text-3xl font-semibold text-slate-950">Admin reporting dashboard</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Server-side rollups for Function Entry, Daily Income, Daily Billing, Vendor Entry, and Admin Income.
                </p>
            </div>
            <a href="{{ route('admin.reports.index', $filters->query()) }}" class="crm-button crm-button-secondary justify-center">
                Open report hub
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @include('admin.reports.partials.module-tabs', ['filters' => $filters])

        @include('admin.reports.partials.filter-card', [
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'showModule' => true,
            'supportsVenue' => true,
            'supportsVendor' => true,
            'supportsPackageService' => true,
            'resetRoute' => route('admin.dashboard'),
        ])

        <section class="crm-summary-grid">
            @foreach ($metrics['primary'] as $card)
                <article class="crm-kpi">
                    <p class="crm-section-title">{{ $card['label'] }}</p>
                    <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($card['value_minor']) }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $card['entries'] }} rows in current scope</p>
                </article>
            @endforeach
        </section>

        <section class="crm-summary-grid">
            @foreach ($metrics['secondary'] as $card)
                <article class="crm-kpi">
                    <p class="crm-section-title">{{ $card['label'] }}</p>
                    <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($card['value_minor']) }}</p>
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <span class="text-sm text-slate-500">{{ $card['entries'] }} rows</span>
                        <a href="{{ route(\App\Support\Reports\ReportModule::routeName($card['module']), $filters->query()) }}" class="crm-button crm-button-secondary px-4 py-2">
                            Open
                        </a>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Report launcher</p>
                <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Module totals and exports</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Dashboard metrics and export workbooks read from dedicated report queries. Employee venue session state is never used here; every report stays explicit and admin-scoped.
                </p>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach ($metrics['modules'] as $moduleCard)
                        <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $moduleCard['label'] }}</p>
                                    <p class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-500">{{ $moduleCard['entries'] }} rows</p>
                                </div>
                                <span class="crm-chip bg-white text-slate-600">{{ Money::formatMinor($moduleCard['value_minor']) }}</span>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-3">
                                <a href="{{ route(\App\Support\Reports\ReportModule::routeName($moduleCard['module']), $filters->query()) }}" class="crm-button crm-button-primary px-4 py-2">
                                    Open report
                                </a>
                                <a href="{{ route(\App\Support\Reports\ReportModule::exportRouteName($moduleCard['module']), $filters->query()) }}" class="crm-button crm-button-secondary px-4 py-2">
                                    Export
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Scope Notes</p>
                <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Reporting rules</h2>
                <ul class="mt-6 space-y-4 text-sm leading-6 text-slate-600">
                    <li>Admin reports run on explicit venue, user, role, and date filters only.</li>
                    <li>Function reporting reads stored parent totals, not child-table recalculation on the fly.</li>
                    <li>Admin Income remains global-only until a venue dimension is intentionally added to its schema.</li>
                    <li>All workbook cells and UI totals stay plain numeric with no symbol or code prefixes.</li>
                </ul>
            </article>
        </section>
    </div>
</x-app-layout>
