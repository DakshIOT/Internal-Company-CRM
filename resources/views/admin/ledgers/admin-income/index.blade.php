@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Admin Income</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Admin Income Workspace</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Global admin-only income tracking with date totals, grand totals, and secured attachments.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
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

        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="space-y-4">
                @forelse ($entries as $entry)
                    <article class="crm-panel p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="crm-section-title">{{ optional($entry->entry_date)->format('d M Y') }}</p>
                                <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $entry->name }}</h2>
                                <p class="mt-2 text-sm text-slate-500">{{ $entry->notes ?: 'No notes added yet.' }}</p>
                            </div>
                            <span class="crm-chip bg-white text-slate-500">{{ $entry->attachments_count }} files</span>
                        </div>

                        <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                <p class="crm-section-title">Amount</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ Money::formatMinor($entry->amount_minor) }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.admin-income.edit', $entry) }}" class="crm-button crm-button-primary">Open entry</a>
                                <a href="{{ route('admin.admin-income.edit', $entry) }}" class="crm-button crm-button-secondary">View</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="crm-panel p-8 text-center text-sm text-slate-500">
                        No admin income entries found for the current filter.
                    </article>
                @endforelse

                {{ $entries->links() }}
            </div>

            <aside class="xl:sticky xl:top-24 xl:self-start">
                @include('ledgers.partials.sidebar-summary', [
                    'entry' => new \App\Models\AdminIncomeEntry(),
                    'workspaceTotals' => $workspaceTotals,
                ])
            </aside>
        </section>
    </div>
</x-app-layout>
