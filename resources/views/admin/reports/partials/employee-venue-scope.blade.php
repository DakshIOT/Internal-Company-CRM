@php
    $moduleRoute = $moduleRoute ?? 'admin.reports.functions.index';
    $selectedEmployee = collect($filterOptions['users'] ?? [])->firstWhere('id', $filters->userId);
@endphp

<section class="crm-panel p-5">
    <div class="flex flex-col gap-4 border-b border-slate-100 pb-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="crm-section-title">Report Scope</p>
            <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950">Employee -> Venue scope</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Pick one employee first. Then optionally narrow to one assigned venue.
            </p>
        </div>
        @if ($filters->hasEmployeeScope())
            <div class="flex flex-wrap items-center gap-2">
                @if (! empty($exportRoute))
                    <a href="{{ route($exportRoute, $filters->query()) }}" class="crm-button crm-button-secondary justify-center">
                        Export current report
                    </a>
                @endif
                <a href="{{ route('admin.reports.export-all', $filters->query()) }}" class="crm-button crm-button-secondary justify-center">
                    Export all reports
                </a>
            </div>
        @endif
    </div>

    <form method="GET" action="{{ route($moduleRoute) }}" class="mt-5 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
        <label class="crm-field">
            <span class="crm-field-label">Employee</span>
            <select name="user_id" class="crm-input" required>
                <option value="">Select employee</option>
                @foreach ($filterOptions['users'] as $userOption)
                    <option value="{{ $userOption->id }}" @selected($filters->userId === (int) $userOption->id)>{{ $userOption->name }} / {{ $userOption->roleLabel() }}</option>
                @endforeach
            </select>
        </label>

        <label class="crm-field">
            <span class="crm-field-label">Venue</span>
            <select name="venue_id" class="crm-input" @disabled(! $filters->hasEmployeeScope())>
                <option value="">{{ $filters->hasEmployeeScope() ? 'All assigned venues' : 'Select employee first' }}</option>
                @foreach ($filterOptions['venues'] as $venue)
                    <option value="{{ $venue->id }}" @selected($filters->venueId === (int) $venue->id)>{{ $venue->name }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="crm-button crm-button-primary justify-center">Load report</button>
    </form>

    @if ($filters->hasEmployeeScope())
        <div class="mt-5 space-y-2">
            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">
                {{ $selectedEmployee?->name ?? 'Employee' }} / Venue tabs
            </p>
            <div class="crm-scroll-strip">
                <div class="flex min-w-max gap-2 sm:gap-3">
                    <a href="{{ route($moduleRoute, collect($filters->query())->except('venue_id')->all()) }}" class="crm-tab {{ ! $filters->venueId ? 'crm-tab-active' : '' }}">
                        All venues
                    </a>
                    @foreach ($filterOptions['venues'] as $venue)
                        <a
                            href="{{ route($moduleRoute, array_merge($filters->query(), ['venue_id' => $venue->id])) }}"
                            class="crm-tab {{ $filters->venueId === (int) $venue->id ? 'crm-tab-active' : '' }}"
                        >
                            {{ $venue->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</section>
