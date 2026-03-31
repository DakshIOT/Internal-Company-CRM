<x-app-layout>
    <x-slot name="header">
        <div class="crm-toolbar">
            <div>
                <p class="crm-section-title">Admin Dashboard</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Command center</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Fast access to master data, reports, and the admin-only ledger without extra navigation noise.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.reports.index') }}" class="crm-button crm-button-secondary justify-center">Open reports</a>
                <a href="{{ route('admin.master-data.employees.index') }}" class="crm-button crm-button-primary justify-center">Manage employees</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="crm-summary-grid">
            @foreach ($cards as $card)
                <article class="crm-kpi">
                    <p class="crm-section-title">{{ $card['label'] }}</p>
                    <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $card['value'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $card['hint'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="crm-panel p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="crm-section-title">Admin Workflow Guide</p>
                    <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Use this order to set up the CRM correctly</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                        Venues control employee access, available services, package choices, and vendor slots. Keep setup venue-first so reports and exports stay accurate.
                    </p>
                </div>
                <span class="crm-chip bg-cyan-50 text-cyan-700">Vendor Entry is available only for Employee Type B</span>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    'Create the venue and confirm its 4 vendor slots are ready.',
                    'Create the employee and choose the correct role before assigning access.',
                    'Open employee assignments and enable the exact venue for that employee.',
                    'Inside each enabled venue, select the allowed services and packages only.',
                    'Use admin reports with explicit employee and venue filters before exporting.',
                ] as $step)
                    <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="text-sm leading-6 text-slate-600">{{ $step }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Control surfaces</p>
                <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">What admin controls here</h2>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach ([
                        ['title' => 'Venues and employees', 'text' => 'Assign venue access, employee type, frozen fund scope, and login status.'],
                        ['title' => 'Services and packages', 'text' => 'Control the exact service and package options available in each employee venue workflow.'],
                        ['title' => 'Reports and exports', 'text' => 'Use employee-wise and venue-wise filters, then export the exact current scope.'],
                        ['title' => 'Admin income', 'text' => 'Track admin-only income rows with the same secure attachment pattern used elsewhere.'],
                    ] as $panel)
                        <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                            <p class="text-sm font-semibold text-slate-900">{{ $panel['title'] }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $panel['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Quick actions</p>
                <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Open what you need</h2>
                <ul class="mt-6 space-y-3">
                    @foreach ($quickLinks as $link)
                        <li class="flex items-center justify-between gap-4 rounded-[1.25rem] border border-slate-100 bg-slate-50 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-800">{{ $link['label'] }}</span>
                            <a href="{{ route($link['route']) }}" class="crm-button crm-button-secondary px-4 py-2">Open</a>
                        </li>
                    @endforeach
                </ul>
            </article>
        </section>
    </div>
</x-app-layout>
