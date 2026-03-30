<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">Admin command center</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Global CRM entrypoint for architecture review, user access governance, and the phased build rollout.
            </p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <article class="crm-kpi">
                    <p class="crm-section-title">{{ $card['label'] }}</p>
                    <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $card['value'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $card['hint'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Phase 2 active</p>
                <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Admin master-data control surfaces</h2>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-[1.5rem] bg-slate-950 p-5 text-white">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Master data</p>
                        <p class="mt-3 text-lg font-semibold">Venues, employees, services, packages</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Admin can now control the records that unlock every later workflow.
                        </p>
                    </div>
                    <div class="rounded-[1.5rem] bg-cyan-50 p-5 text-slate-900">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-700">Access mapping</p>
                        <p class="mt-3 text-lg font-semibold">Venue, service, package assignment</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Employee permissions now stay explicit and venue-scoped instead of implicit.
                        </p>
                    </div>
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Quick actions</p>
                <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Open a control surface</h2>
                <ul class="mt-6 space-y-4">
                    @foreach ($quickLinks as $link)
                        <li class="flex items-center justify-between gap-4 rounded-[1.25rem] border border-slate-100 bg-slate-50 px-4 py-4">
                            <span class="text-sm font-semibold text-slate-800">{{ $link['label'] }}</span>
                            <a href="{{ route($link['route']) }}" class="crm-button crm-button-secondary px-4 py-2">Open</a>
                        </li>
                    @endforeach
                </ul>
            </article>
        </section>
    </div>
</x-app-layout>
