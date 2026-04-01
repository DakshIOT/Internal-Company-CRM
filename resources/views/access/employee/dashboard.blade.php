<x-app-layout>
    <x-slot name="header">
        <div class="crm-page-header">
            <div class="crm-page-heading">
                <p class="crm-section-title">Dashboard</p>
                <h1 class="crm-page-title">{{ $roleLabel }} workspace</h1>
                <p class="crm-page-description">{{ $headline }}</p>
            </div>
            <div class="crm-page-header-actions">
                <span class="crm-chip bg-slate-950 text-white">{{ $venue->name }}</span>
                <a href="{{ route('venues.select') }}" class="crm-button crm-button-secondary justify-center px-4 py-2.5 crm-print-hidden">
                    Change venue
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Current venue context</p>
                <div class="mt-4 flex flex-col gap-4 rounded-[1.75rem] bg-slate-950 p-6 text-white sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-200">Selected venue</p>
                        <h2 class="mt-2 font-display text-3xl font-semibold">{{ $venue->name }}</h2>
                        <p class="mt-3 text-sm text-slate-300">
                            Every dashboard count, list, and total will stay scoped to this venue in later phases.
                        </p>
                    </div>
                    <a href="{{ route('venues.select') }}" class="crm-button bg-white text-slate-950 hover:bg-slate-100">
                        Choose another venue
                    </a>
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Foundation status</p>
                <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Phase 4 ledger layer</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Function Entry is ready in this venue with staged package, charge, installment, discount, and attachment handling. Daily Income and Daily Billing are now live for supported roles, and Vendor Entry is live for Employee Type B.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="crm-chip bg-cyan-50 text-cyan-700">Function Entry</span>
                    @if (in_array('Daily Income', $modules, true))
                        <span class="crm-chip bg-cyan-50 text-cyan-700">Daily Income</span>
                    @endif
                    @if (in_array('Daily Billing', $modules, true))
                        <span class="crm-chip bg-cyan-50 text-cyan-700">Daily Billing</span>
                    @endif
                    @if (in_array('Vendor Entry', $modules, true))
                        <span class="crm-chip bg-cyan-50 text-cyan-700">Vendor Entry</span>
                    @endif
                </div>
            </article>
        </section>

        <section class="crm-panel p-6">
            <p class="crm-section-title">Your permitted modules</p>
            <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Mapped from your role</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($modules as $module)
                    <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold text-slate-900">{{ $module }}</span>
                            <span class="crm-chip bg-white text-slate-500">{{ in_array($module, ['Function Entry', 'Daily Income', 'Daily Billing', 'Vendor Entry'], true) ? 'Live' : 'Planned' }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            @if ($module === 'Function Entry')
                                This module is active now for your current venue, including packages, adjustments, installments, discounts, and attachments.
                            @elseif ($module === 'Daily Income')
                                This module is active now for the selected venue, including date totals, grand totals, and secure attachment handling.
                            @elseif ($module === 'Daily Billing')
                                This module is active now for the selected venue, including date totals, grand totals, and secure attachment handling.
                            @elseif ($module === 'Vendor Entry')
                                This module is active now for the selected venue with four admin-defined vendor slots, vendor totals, and secure attachment handling.
                            @else
                                This module is unlocked for your role in the approved plan and will be built in the next phases.
                            @endif
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
