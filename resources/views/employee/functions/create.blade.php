<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Function Entry</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Create Function Entry</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Start with the date, function name, notes, and any base files. After save, the action center opens for packages, extra charges, installments, and discounts.
                </p>
            </div>
            <span class="crm-chip bg-slate-950 text-white">{{ $currentVenue->name }}</span>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('employee.functions.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        @csrf

        @include('employee.functions.partials.form', [
            'currentVenue' => $currentVenue,
            'functionEntry' => $functionEntry,
            'isEditing' => false,
        ])

        <aside class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">What happens next</p>
                <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <p>Create the base entry first.</p>
                    <p>Open the action center to add assigned packages and configure service rows.</p>
                    <p>Track extra charges, installments, discounts, and attachments without leaving the entry.</p>
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Rules locked in</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="crm-chip bg-cyan-50 text-cyan-700">Venue scoped</span>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">Integer money</span>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">No currency symbol</span>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">Multiple attachments</span>
                </div>
            </article>
        </aside>
    </form>
</x-app-layout>
