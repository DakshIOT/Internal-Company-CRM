@php
    use App\Support\Reports\ReportModule;

    $action = $action ?? url()->current();
    $resetRoute = $resetRoute ?? url()->current();
    $showModule = $showModule ?? false;
    $supportsVenue = $supportsVenue ?? true;
    $supportsVendor = $supportsVendor ?? false;
    $supportsPackageService = $supportsPackageService ?? false;
    $supportsSearch = $supportsSearch ?? true;
    $requiresEmployeeSelection = $requiresEmployeeSelection ?? false;
    $selectedModule = $showModule ? ($filters->module ?? '') : ($module ?? '');
    $selectedVenue = collect($filterOptions['venues'] ?? [])->firstWhere('id', $filters->venueId);
    $selectedEmployee = collect($filterOptions['users'] ?? [])->firstWhere('id', $filters->userId);
    $selectedVendor = collect($filterOptions['vendors'] ?? [])->firstWhere('id', $filters->vendorId);
    $selectedPackage = collect($filterOptions['packages'] ?? [])->firstWhere('id', $filters->packageId);
    $selectedService = collect($filterOptions['services'] ?? [])->firstWhere('id', $filters->serviceId);
    $canExport = ! empty($exportRoute) && (! $requiresEmployeeSelection || $filters->userId);
@endphp

<section
    class="crm-panel p-5"
    x-data="{ mobileOpen: false, moduleValue: @js($selectedModule) }"
>
    <div class="flex flex-col gap-4 border-b border-slate-100 pb-4 md:flex-row md:items-start md:justify-between">
        <div>
            <p class="crm-section-title">Shared Filters</p>
            <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950">Employee-first report scope</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Select one employee first, then narrow that employee's data by venue, date, package, service, or vendor where relevant.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($filters->userId)
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $selectedEmployee?->name ?? 'Employee' }}</span>
                @endif
                @if ($filters->dateFrom || $filters->dateTo)
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $filters->dateFrom ?? 'Start' }} to {{ $filters->dateTo ?? 'End' }}</span>
                @endif
                @if ($supportsVenue && $filters->venueId)
                    <span class="crm-chip bg-slate-100 text-slate-600">{{ $selectedVenue?->name ?? 'Venue' }}</span>
                @endif
                @if ($supportsVendor && $filters->vendorId)
                    <span class="crm-chip bg-slate-100 text-slate-600">{{ $selectedVendor?->name ?? 'Vendor' }}</span>
                @endif
                @if ($supportsPackageService && $filters->packageId)
                    <span class="crm-chip bg-slate-100 text-slate-600">{{ $selectedPackage?->name ?? 'Package' }}</span>
                @endif
                @if ($supportsPackageService && $filters->serviceId)
                    <span class="crm-chip bg-slate-100 text-slate-600">{{ $selectedService?->name ?? 'Service' }}</span>
                @endif
                @if ($filters->search)
                    <span class="crm-chip bg-slate-100 text-slate-600">Search active</span>
                @endif
                @unless ($filters->hasActiveFilters())
                    <span class="crm-chip bg-slate-100 text-slate-500">No active filters</span>
                @endunless
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            @if ($canExport)
                <a href="{{ route($exportRoute, $filters->query()) }}" class="crm-button crm-button-secondary justify-center">Export current report</a>
            @elseif (! empty($exportRoute))
                <span class="crm-button crm-button-secondary cursor-not-allowed justify-center opacity-60">Select employee to export</span>
            @endif
            <button type="button" class="crm-button crm-button-secondary justify-center md:hidden" @click="mobileOpen = ! mobileOpen">
                Filters
            </button>
        </div>
    </div>

    <form method="GET" action="{{ $action }}" class="mt-5 space-y-5">
        @if ($requiresEmployeeSelection && ! $filters->userId)
            <div class="rounded-[1.25rem] border border-cyan-100 bg-cyan-50 px-4 py-4 text-sm leading-6 text-cyan-800">
                Choose an employee first. Once selected, the venue list and report rows will scope only to that employee.
            </div>
        @endif

        <div class="hidden md:block">
            <div class="grid gap-4 lg:grid-cols-4">
                @if ($showModule)
                    <label class="crm-field">
                        <span class="crm-field-label">Module</span>
                        <select name="module" x-model="moduleValue" class="crm-input">
                            <option value="">All modules</option>
                            @foreach ($filterOptions['modules'] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected($filters->module === $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="crm-field">
                    <span class="crm-field-label">Employee</span>
                    <select name="user_id" class="crm-input">
                        <option value="">Select employee</option>
                        @foreach ($filterOptions['users'] as $userOption)
                            <option value="{{ $userOption->id }}" @selected($filters->userId === (int) $userOption->id)>{{ $userOption->name }} / {{ $userOption->roleLabel() }}</option>
                        @endforeach
                    </select>
                </label>

                @if ($supportsVenue)
                    <label class="crm-field">
                        <span class="crm-field-label">Venue</span>
                        <select name="venue_id" class="crm-input" @disabled(! $filters->userId)>
                            <option value="">{{ $filters->userId ? 'All assigned venues' : 'Select employee first' }}</option>
                            @foreach ($filterOptions['venues'] as $venue)
                                <option value="{{ $venue->id }}" @selected($filters->venueId === (int) $venue->id)>{{ $venue->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="crm-field">
                    <span class="crm-field-label">Date From</span>
                    <input type="date" name="date_from" value="{{ $filters->dateFrom }}" class="crm-input">
                </label>

                <label class="crm-field">
                    <span class="crm-field-label">Date To</span>
                    <input type="date" name="date_to" value="{{ $filters->dateTo }}" class="crm-input">
                </label>

                @if ($supportsVendor)
                    <label class="crm-field" x-show="moduleValue === '{{ ReportModule::VENDOR_ENTRIES }}' || moduleValue === ''" x-cloak>
                        <span class="crm-field-label">Vendor</span>
                        <select name="vendor_id" class="crm-input" @disabled(! $filters->userId)>
                            <option value="">{{ $filters->userId ? 'All vendors' : 'Select employee first' }}</option>
                            @foreach ($filterOptions['vendors'] as $vendor)
                                <option value="{{ $vendor->id }}" @selected($filters->vendorId === (int) $vendor->id)>{{ $vendor->name }} / {{ $vendor->venue->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                @if ($supportsPackageService)
                    <label class="crm-field" x-show="moduleValue === '{{ ReportModule::FUNCTIONS }}' || moduleValue === ''" x-cloak>
                        <span class="crm-field-label">Package</span>
                        <select name="package_id" class="crm-input" @disabled(! $filters->userId)>
                            <option value="">{{ $filters->userId ? 'All packages' : 'Select employee first' }}</option>
                            @foreach ($filterOptions['packages'] as $package)
                                <option value="{{ $package->id }}" @selected($filters->packageId === (int) $package->id)>{{ $package->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="crm-field" x-show="moduleValue === '{{ ReportModule::FUNCTIONS }}' || moduleValue === ''" x-cloak>
                        <span class="crm-field-label">Service</span>
                        <select name="service_id" class="crm-input" @disabled(! $filters->userId)>
                            <option value="">{{ $filters->userId ? 'All services' : 'Select employee first' }}</option>
                            @foreach ($filterOptions['services'] as $service)
                                <option value="{{ $service->id }}" @selected($filters->serviceId === (int) $service->id)>{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                @if ($supportsSearch)
                    <label class="crm-field lg:col-span-2">
                        <span class="crm-field-label">Search</span>
                        <input type="text" name="search" value="{{ $filters->search }}" class="crm-input" placeholder="Search name or notes">
                    </label>
                @endif
            </div>
        </div>

        <div class="space-y-4 md:hidden" x-show="mobileOpen" x-cloak>
            @if ($showModule)
                <label class="crm-field">
                    <span class="crm-field-label">Module</span>
                    <select name="module" x-model="moduleValue" class="crm-input">
                        <option value="">All modules</option>
                        @foreach ($filterOptions['modules'] as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected($filters->module === $optionValue)>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <label class="crm-field">
                <span class="crm-field-label">Employee</span>
                <select name="user_id" class="crm-input">
                    <option value="">Select employee</option>
                    @foreach ($filterOptions['users'] as $userOption)
                        <option value="{{ $userOption->id }}" @selected($filters->userId === (int) $userOption->id)>{{ $userOption->name }} / {{ $userOption->roleLabel() }}</option>
                    @endforeach
                </select>
            </label>

            @if ($supportsVenue)
                <label class="crm-field">
                    <span class="crm-field-label">Venue</span>
                    <select name="venue_id" class="crm-input" @disabled(! $filters->userId)>
                        <option value="">{{ $filters->userId ? 'All assigned venues' : 'Select employee first' }}</option>
                        @foreach ($filterOptions['venues'] as $venue)
                            <option value="{{ $venue->id }}" @selected($filters->venueId === (int) $venue->id)>{{ $venue->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="crm-field">
                    <span class="crm-field-label">Date From</span>
                    <input type="date" name="date_from" value="{{ $filters->dateFrom }}" class="crm-input">
                </label>
                <label class="crm-field">
                    <span class="crm-field-label">Date To</span>
                    <input type="date" name="date_to" value="{{ $filters->dateTo }}" class="crm-input">
                </label>
            </div>

            @if ($supportsVendor)
                <label class="crm-field" x-show="moduleValue === '{{ ReportModule::VENDOR_ENTRIES }}' || moduleValue === ''" x-cloak>
                    <span class="crm-field-label">Vendor</span>
                    <select name="vendor_id" class="crm-input" @disabled(! $filters->userId)>
                        <option value="">{{ $filters->userId ? 'All vendors' : 'Select employee first' }}</option>
                        @foreach ($filterOptions['vendors'] as $vendor)
                            <option value="{{ $vendor->id }}" @selected($filters->vendorId === (int) $vendor->id)>{{ $vendor->name }} / {{ $vendor->venue->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            @if ($supportsPackageService)
                <label class="crm-field" x-show="moduleValue === '{{ ReportModule::FUNCTIONS }}' || moduleValue === ''" x-cloak>
                    <span class="crm-field-label">Package</span>
                    <select name="package_id" class="crm-input" @disabled(! $filters->userId)>
                        <option value="">{{ $filters->userId ? 'All packages' : 'Select employee first' }}</option>
                        @foreach ($filterOptions['packages'] as $package)
                            <option value="{{ $package->id }}" @selected($filters->packageId === (int) $package->id)>{{ $package->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="crm-field" x-show="moduleValue === '{{ ReportModule::FUNCTIONS }}' || moduleValue === ''" x-cloak>
                    <span class="crm-field-label">Service</span>
                    <select name="service_id" class="crm-input" @disabled(! $filters->userId)>
                        <option value="">{{ $filters->userId ? 'All services' : 'Select employee first' }}</option>
                        @foreach ($filterOptions['services'] as $service)
                            <option value="{{ $service->id }}" @selected($filters->serviceId === (int) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            @if ($supportsSearch)
                <label class="crm-field">
                    <span class="crm-field-label">Search</span>
                    <input type="text" name="search" value="{{ $filters->search }}" class="crm-input" placeholder="Search name or notes">
                </label>
            @endif
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row">
            <button type="submit" class="crm-button crm-button-primary justify-center">Apply filters</button>
            <a href="{{ $resetRoute }}" class="crm-button crm-button-secondary justify-center">Reset</a>
        </div>
    </form>
</section>
