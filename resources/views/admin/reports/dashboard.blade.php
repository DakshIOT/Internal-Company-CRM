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

        <section class="crm-summary-grid">
            <article class="crm-kpi">
                <p class="crm-section-title">Employees</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $employeeCount }}</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">Internal employee accounts in scope.</p>
            </article>
            @foreach ($metrics['primary'] as $card)
                <article class="crm-kpi">
                    <p class="crm-section-title">{{ $card['label'] }}</p>
                    <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($card['value_minor']) }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $card['entries'] }} rows recorded</p>
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
                        <a href="{{ route(\App\Support\Reports\ReportModule::routeName($card['module'])) }}" class="crm-button crm-button-secondary px-4 py-2">
                            Open
                        </a>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="crm-panel p-6">
            <p class="crm-section-title">Report flow</p>
            <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Employee -> Venue -> Table -> Export</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Dashboard stays global. Report pages are employee-first: choose employee, optionally choose venue, view table rows, then export in Excel.
            </p>
            <div class="mt-5">
                <a href="{{ route('admin.reports.index') }}" class="crm-button crm-button-primary px-5 py-3">Open employee-wise reports</a>
            </div>
        </section>
    </div>
</x-app-layout>
