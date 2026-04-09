<x-app-layout>
    <x-slot name="header">
        <div class="crm-toolbar">
            <div>
                <p class="crm-section-title">Admin Reports</p>
                <h1 class="font-display text-3xl font-semibold text-slate-950">Report hub</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Select employee first. Then each report opens in employee scope with optional venue narrowing.
                </p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="crm-button crm-button-secondary justify-center">
                Back to report dashboard
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="crm-panel p-6">
            <p class="crm-section-title">Step 1</p>
            <h2 class="mt-3 font-display text-2xl font-semibold text-slate-950">Choose employee and open report</h2>
            <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-5 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                <label class="crm-field">
                    <span class="crm-field-label">Employee</span>
                    <select name="user_id" class="crm-input" required>
                        <option value="">Select employee</option>
                        @foreach ($filterOptions['users'] as $userOption)
                            <option value="{{ $userOption->id }}" @selected($filters->userId === (int) $userOption->id)>
                                {{ $userOption->name }} / {{ $userOption->roleLabel() }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="crm-field">
                    <span class="crm-field-label">Report</span>
                    <select name="module" class="crm-input">
                        @foreach ($filterOptions['modules'] as $module => $label)
                            <option value="{{ $module }}" @selected(($filters->module ?: \App\Support\Reports\ReportModule::FUNCTIONS) === $module)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="crm-button crm-button-primary justify-center">Open report</button>
            </form>
            @if ($filters->hasEmployeeScope())
                <div class="mt-4">
                    <a href="{{ route('admin.reports.export-all', $filters->query()) }}" class="crm-button crm-button-secondary justify-center">
                        Export all reports for selected employee
                    </a>
                </div>
            @endif
        </section>

        <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($reportLinks as $link)
                <article class="crm-panel p-6">
                    <p class="crm-section-title">{{ $link['label'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Employee-wise table view, venue-wise narrowing, and Excel export.</p>
                </article>
            @endforeach
        </section>
    </div>
</x-app-layout>
