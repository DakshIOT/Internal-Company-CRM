@php
    use App\Support\Money;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">Function Entry report</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Parent totals, package rollups, and service rollups on one admin-only reporting surface.
            </p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @include('admin.reports.partials.module-tabs', ['filters' => $filters, 'module' => $module])

        @include('admin.reports.partials.filter-card', [
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'module' => $module,
            'supportsVenue' => true,
            'supportsPackageService' => true,
            'resetRoute' => route('admin.reports.functions.index'),
            'exportRoute' => 'admin.reports.functions.export',
        ])

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="crm-kpi"><p class="crm-section-title">Function Total</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($summary['function_total_minor']) }}</p></article>
            <article class="crm-kpi"><p class="crm-section-title">Paid</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($summary['paid_total_minor']) }}</p></article>
            <article class="crm-kpi"><p class="crm-section-title">Pending</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($summary['pending_total_minor']) }}</p></article>
            <article class="crm-kpi"><p class="crm-section-title">Net After Frozen Fund</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($summary['net_total_after_frozen_fund_minor']) }}</p></article>
        </section>

        <section class="space-y-6 2xl:grid 2xl:grid-cols-[minmax(0,1fr)_22rem] 2xl:gap-6 2xl:space-y-0">
            <div class="space-y-6">
                <div class="space-y-4 lg:hidden">
                    @forelse ($entries as $entry)
                        <article class="crm-panel p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $entry->name }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500">{{ optional($entry->entry_date)->format('d M Y') }}</p>
                                </div>
                                <span class="crm-chip bg-cyan-50 text-cyan-700">{{ Money::formatMinor($entry->function_total_minor) }}</span>
                            </div>
                            <div class="mt-4 grid gap-3 text-sm text-slate-600">
                                <div>Venue: <span class="font-semibold text-slate-900">{{ $entry->venue->name ?? '-' }}</span></div>
                                <div>Employee: <span class="font-semibold text-slate-900">{{ $entry->user->name ?? '-' }}</span></div>
                                <div>Type: <span class="font-semibold text-slate-900">{{ $entry->user?->roleLabel() ?? '-' }}</span></div>
                                <div>Paid / Pending: <span class="font-semibold text-slate-900">{{ Money::formatMinor($entry->paid_total_minor) }} / {{ Money::formatMinor($entry->pending_total_minor) }}</span></div>
                                <div>Packages / Files: <span class="font-semibold text-slate-900">{{ $entry->packages_count }} / {{ $entry->attachments_count }}</span></div>
                            </div>
                        </article>
                    @empty
                        <article class="crm-panel p-8 text-center text-sm text-slate-500">No function entries match the current filters.</article>
                    @endforelse
                </div>

                <section class="crm-panel hidden overflow-hidden lg:block">
                    <div class="crm-table-wrap rounded-none border-0">
                        <table class="crm-table">
                            <thead>
                                <tr>
                                    <th>Entry Date</th>
                                    <th>Venue</th>
                                    <th>Employee</th>
                                    <th>Employee Type</th>
                                    <th>Name</th>
                                    <th>Function Total</th>
                                    <th>Paid</th>
                                    <th>Pending</th>
                                    <th>Frozen Fund</th>
                                    <th>Net After Frozen Fund</th>
                                    <th>Packages</th>
                                    <th>Files</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($entries as $entry)
                                    <tr>
                                        <td>{{ optional($entry->entry_date)->format('d M Y') }}</td>
                                        <td>{{ $entry->venue->name ?? '-' }}</td>
                                        <td>{{ $entry->user->name ?? '-' }}</td>
                                        <td>{{ $entry->user?->roleLabel() ?? '-' }}</td>
                                        <td>{{ $entry->name }}</td>
                                        <td>{{ Money::formatMinor($entry->function_total_minor) }}</td>
                                        <td>{{ Money::formatMinor($entry->paid_total_minor) }}</td>
                                        <td>{{ Money::formatMinor($entry->pending_total_minor) }}</td>
                                        <td>{{ Money::formatMinor($entry->frozen_fund_minor) }}</td>
                                        <td>{{ Money::formatMinor($entry->net_total_after_frozen_fund_minor) }}</td>
                                        <td>{{ $entry->packages_count }}</td>
                                        <td>{{ $entry->attachments_count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="px-4 py-8 text-center text-sm text-slate-500">No function entries match the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 md:grid-cols-2 2xl:grid-cols-1">
                <article class="crm-panel p-6">
                    <p class="crm-section-title">Report summary</p>
                    <div class="mt-4 grid gap-3 text-sm text-slate-600">
                        <div class="flex items-center justify-between gap-3"><span>Entry count</span><span class="font-semibold text-slate-950">{{ $summary['entry_count'] }}</span></div>
                        <div class="flex items-center justify-between gap-3"><span>Function total</span><span class="font-semibold text-slate-950">{{ Money::formatMinor($summary['function_total_minor']) }}</span></div>
                        <div class="flex items-center justify-between gap-3"><span>Paid</span><span class="font-semibold text-slate-950">{{ Money::formatMinor($summary['paid_total_minor']) }}</span></div>
                        <div class="flex items-center justify-between gap-3"><span>Pending</span><span class="font-semibold text-slate-950">{{ Money::formatMinor($summary['pending_total_minor']) }}</span></div>
                        <div class="flex items-center justify-between gap-3"><span>Frozen fund</span><span class="font-semibold text-slate-950">{{ Money::formatMinor($summary['frozen_fund_minor']) }}</span></div>
                    </div>
                </article>

                <article class="crm-panel p-6">
                    <p class="crm-section-title">Package totals</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($packageTotals as $row)
                            <div class="flex items-center justify-between gap-3 rounded-[1.25rem] border border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $row['package_name'] }}</p>
                                    <p class="text-slate-500">{{ $row['entry_count'] }} rows</p>
                                </div>
                                <span class="font-semibold text-slate-950">{{ Money::formatMinor($row['total_minor']) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No package totals for the current scope.</p>
                        @endforelse
                    </div>
                </article>

                <article class="crm-panel p-6">
                    <p class="crm-section-title">Service totals</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($serviceTotals as $row)
                            <div class="flex items-center justify-between gap-3 rounded-[1.25rem] border border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $row['service_name'] }}</p>
                                    <p class="text-slate-500">{{ $row['line_count'] }} lines</p>
                                </div>
                                <span class="font-semibold text-slate-950">{{ Money::formatMinor($row['total_minor']) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No service totals for the current scope.</p>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>

        {{ $entries->links() }}
    </div>
</x-app-layout>
