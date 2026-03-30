<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">{{ $roleLabel }} workspace</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $headline }}</p>
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
                <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Phase 3 foundation</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Function Entry is ready in this venue with staged package, charge, installment, discount, and attachment handling. The remaining ledger and reporting modules are still intentionally deferred.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="crm-chip bg-cyan-50 text-cyan-700">Function Entry</span>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">Venue enforcement</span>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">Responsive shell</span>
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
                            <span class="crm-chip bg-white text-slate-500">{{ $module === 'Function Entry' ? 'Live' : 'Planned' }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            {{ $module === 'Function Entry'
                                ? 'This module is active now for your current venue, including packages, adjustments, installments, discounts, and attachments.'
                                : 'This module is unlocked for your role in the approved plan and will be built in the next phases.' }}
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
