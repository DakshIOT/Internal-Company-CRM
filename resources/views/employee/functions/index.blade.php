@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Function Entry</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Function Entries</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Every entry here is locked to {{ $currentVenue->name }} and recalculated server-side after every package, charge, installment, and discount change.
                </p>
            </div>
            <a href="{{ route('employee.functions.create') }}" class="crm-button crm-button-primary justify-center">
                Create function entry
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="crm-kpi">
                <p class="crm-section-title">Entries in venue</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $workspaceTotals['grand']['entry_count'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Function total</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['function_total_minor']) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Paid</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['paid_total_minor']) }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Pending</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['pending_total_minor']) }}</p>
            </article>
        </section>

        <section class="crm-panel p-5">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_14rem_auto]">
                <x-text-input name="search" :value="$filters['search'] ?? ''" class="crm-input w-full" placeholder="Search by function name or notes" />
                <x-text-input name="entry_date" type="date" :value="$filters['entry_date'] ?? ''" class="crm-input w-full" />
                <div class="flex gap-2">
                    <button type="submit" class="crm-button crm-button-secondary w-full justify-center">Filter</button>
                    <a href="{{ route('employee.functions.index') }}" class="crm-button crm-button-secondary w-full justify-center">Reset</a>
                </div>
            </form>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            @forelse ($entries as $entry)
                <article class="crm-panel p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="crm-section-title">{{ optional($entry->entry_date)->format('d M Y') }}</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $entry->name }}</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ $entry->notes ?: 'No notes added yet.' }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $entry->packages_count }} packages</span>
                            <span class="crm-chip bg-white text-slate-500">{{ $entry->attachments_count }} files</span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[1.25rem] bg-slate-50 p-4">
                            <p class="crm-section-title">Function total</p>
                            <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($entry->function_total_minor) }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-slate-50 p-4">
                            <p class="crm-section-title">Paid</p>
                            <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($entry->paid_total_minor) }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-slate-50 p-4">
                            <p class="crm-section-title">Pending</p>
                            <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($entry->pending_total_minor) }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-slate-50 p-4">
                            <p class="crm-section-title">Net after frozen fund</p>
                            <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($entry->net_total_after_frozen_fund_minor) }}</p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('employee.functions.edit', $entry) }}" class="crm-button crm-button-primary">Open action center</a>
                        <a href="{{ route('employee.functions.show', $entry) }}" class="crm-button crm-button-secondary">View</a>
                    </div>
                </article>
            @empty
                <article class="crm-panel p-8 text-center text-sm text-slate-500 xl:col-span-2">
                    No function entries found for the current filter.
                </article>
            @endforelse
        </section>

        {{ $entries->links() }}
    </div>
</x-app-layout>
