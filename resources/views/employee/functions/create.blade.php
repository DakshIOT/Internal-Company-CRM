<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Function Entry</p>
                <h1 class="mt-1 font-display text-2xl font-semibold text-slate-950 sm:text-3xl">Create Function Entry</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Start with the base details only. After save, choose the section you want from the action menu.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="crm-chip bg-slate-950 text-white">{{ $currentVenue->name }}</span>
                <button type="button" onclick="window.print()" class="crm-button crm-button-secondary justify-center px-4 py-2 crm-print-hidden">
                    Print page
                </button>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('employee.functions.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        @csrf

        @include('employee.functions.partials.form', [
            'currentVenue' => $currentVenue,
            'functionEntry' => $functionEntry,
            'isEditing' => false,
        ])

        <aside class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Next step</p>
                <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <p>Save the base entry first.</p>
                    <p>Then pick one section from the action menu: packages, extra charges, installments, or discounts.</p>
                    <p>That keeps the workflow simple and avoids one long crowded page.</p>
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
