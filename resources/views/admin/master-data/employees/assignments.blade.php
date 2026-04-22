@php
    use App\Models\Service;
    use App\Support\Money;

    $inactiveAssignedPackages = $packageAssignments->filter(fn ($assignment) => ! ($assignment->package?->is_active ?? false));
    $selectedPackageIsInactive = $selectedPackageAssignment && ! ($selectedPackageAssignment->package?->is_active ?? false);
    $serviceAssignmentCollection = $serviceAssignments ? collect($serviceAssignments->items()) : collect();
    $inactiveAssignedServices = $serviceAssignmentCollection->filter(fn ($assignment) => ! ($assignment->service?->is_active ?? false));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Admin Master Data</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Employee setup workspace</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Stay inside one employee context: attach venues first, then packages for the selected venue, then the services inside the selected package.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.master-data.employees.index') }}" class="crm-button crm-button-secondary justify-center">Back to users</a>
                <a href="{{ route('admin.master-data.employees.edit', $employee) }}" class="crm-button crm-button-secondary justify-center">Edit account</a>
            </div>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <div class="space-y-6">
        <section class="grid gap-4 2xl:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
            <article class="crm-panel min-w-0 p-6">
                <p class="crm-section-title">Employee</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ $employee->name }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ $employee->email }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $employee->roleLabel() }}</span>
                    <span class="crm-chip {{ $employee->is_active ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-[1.25rem] bg-slate-50 p-4"><p class="crm-section-title">Venues</p><p class="mt-3 text-3xl font-semibold text-slate-950">{{ $assignedVenues->count() }}</p></div>
                    <div class="rounded-[1.25rem] bg-slate-50 p-4"><p class="crm-section-title">Packages</p><p class="mt-3 text-3xl font-semibold text-slate-950">{{ $packageAssignments->count() }}</p></div>
                    <div class="rounded-[1.25rem] bg-slate-50 p-4"><p class="crm-section-title">Services</p><p class="mt-3 text-3xl font-semibold text-slate-950">{{ $selectedPackageServiceCount }}</p></div>
                </div>
            </article>

            <article class="crm-panel min-w-0 p-6">
                <p class="crm-section-title">How this setup works</p>
                <div class="mt-4 grid gap-3 text-sm leading-6 text-slate-600 md:grid-cols-2 2xl:grid-cols-4">
                    <div class="rounded-[1.25rem] bg-slate-50 p-4">1. Create or assign a venue directly inside this employee workspace.</div>
                    <div class="rounded-[1.25rem] bg-slate-50 p-4">2. For the selected venue, create or assign packages only for this employee.</div>
                    <div class="rounded-[1.25rem] bg-slate-50 p-4">3. Inside the selected package, create or assign services and keep repeating that for each venue.</div>
                    <div class="rounded-[1.25rem] bg-slate-50 p-4">4. The same service can appear in multiple packages for the same venue when the workflow needs it.</div>
                </div>
                <div class="mt-4 rounded-[1.25rem] bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                    @if ($employee->supportsFrozenFund())
                        Frozen fund is managed per selected venue for this employee because this user is Employee Type A.
                    @else
                        Frozen fund is not used for this employee type, so this workspace only manages venue, package, and service access.
                    @endif
                </div>
            </article>
        </section>

        <section class="crm-panel p-6" x-data="{ venueSearch: '' }">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="crm-section-title">Venue setup</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-950">Choose the venue context first</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Every package and service decision below belongs to the currently selected venue.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="crm-button crm-button-primary justify-center" x-data x-on:click="$dispatch('open-modal', 'create-venue-modal')">Create venue</button>
                    <button type="button" class="crm-button crm-button-secondary justify-center" x-data x-on:click="$dispatch('open-modal', 'assign-venue-modal')" @disabled($availableVenues->total() === 0)>Assign existing venue</button>
                </div>
            </div>

            @if ($assignedVenues->count() > 6)
                <div class="mt-5 max-w-md">
                    <x-input-label for="venue_search" value="Search assigned venues" />
                    <x-text-input id="venue_search" x-model="venueSearch" class="crm-input mt-2 w-full" placeholder="Search by venue name or code" />
                </div>
            @endif

            @if ($assignedVenues->isEmpty())
                <div class="mt-5 rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center">
                    <h3 class="text-xl font-semibold text-slate-950">No venues assigned yet</h3>
                    <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Start by creating a venue here or assigning one of the existing active venues to this employee.
                    </p>
                </div>
            @else
                <div class="mt-5 flex gap-3 overflow-x-auto pb-2">
                    @foreach ($assignedVenues as $venue)
                        @php($venueNeedle = strtolower(trim($venue->name.' '.$venue->code)))
                        <a
                            href="{{ route('admin.master-data.employees.assignments.edit', ['employee' => $employee, 'venue' => $venue->id]) }}"
                            x-show="!venueSearch || @js($venueNeedle).includes(venueSearch.toLowerCase().trim())"
                            class="min-w-[12rem] rounded-[1.25rem] border px-4 py-3 text-left transition"
                            :class="'{{ $selectedVenue && $selectedVenue->id === $venue->id ? 'border-slate-950 bg-slate-950 text-white shadow-lg shadow-slate-950/15' : 'border-slate-200 bg-white text-slate-700 hover:border-cyan-300 hover:bg-cyan-50' }}'"
                        >
                            <div class="text-xs uppercase tracking-[0.2em] {{ $selectedVenue && $selectedVenue->id === $venue->id ? 'text-white/70' : 'text-slate-400' }}">Venue</div>
                            <div class="mt-2 text-lg font-semibold">{{ $venue->name }}</div>
                            <div class="mt-1 text-xs uppercase tracking-[0.18em] {{ $selectedVenue && $selectedVenue->id === $venue->id ? 'text-white/70' : 'text-slate-400' }}">{{ $venue->code ?: 'No code' }}</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($selectedVenue)
            <section class="grid gap-6 2xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <article class="min-w-0 space-y-6">
                    <section class="crm-panel p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="crm-section-title">Selected venue</p>
                                <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ $selectedVenue->name }}</h2>
                                <p class="mt-2 text-sm text-slate-500">{{ $selectedVenue->code ?: 'No code' }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.master-data.employees.assignments.venues.destroy', [$employee, $selectedVenue]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="crm-button crm-button-secondary justify-center" onclick="return confirm('Remove this venue from the employee setup?')">Remove venue</button>
                            </form>
                        </div>

                        @if ($employee->supportsFrozenFund())
                            <form method="POST" action="{{ route('admin.master-data.employees.assignments.venues.update', [$employee, $selectedVenue]) }}" class="mt-5 rounded-[1.25rem] bg-slate-50 p-4">
                                @csrf
                                @method('PUT')
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                                    <div class="flex-1">
                                        <x-input-label for="selected_frozen_fund" value="Frozen fund for this venue" />
                                        <x-text-input id="selected_frozen_fund" name="frozen_fund" :value="old('frozen_fund', $selectedFrozenFund)" class="crm-input mt-2 w-full" />
                                        <x-input-error :messages="$errors->get('frozen_fund')" class="mt-2" />
                                    </div>
                                    <button type="submit" data-loading-label="Saving..." class="crm-button crm-button-primary justify-center">Save venue setup</button>
                                </div>
                            </form>
                        @endif
                    </section>

                    <section class="crm-panel p-6" x-data="{ packageSearch: '' }">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <p class="crm-section-title">Packages</p>
                                <h2 class="mt-2 text-2xl font-semibold text-slate-950">Packages for {{ $selectedVenue->name }}</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Create new packages here or attach existing ones for this employee inside the selected venue.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="crm-button crm-button-primary justify-center" x-data x-on:click="$dispatch('open-modal', 'create-package-modal')">Create package</button>
                                <button type="button" class="crm-button crm-button-secondary justify-center" x-data x-on:click="$dispatch('open-modal', 'assign-package-modal')" @disabled($availablePackages->total() === 0)>Assign existing package</button>
                            </div>
                        </div>

                        @if ($inactiveAssignedPackages->isNotEmpty())
                            <div class="mt-5 rounded-[1.25rem] border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                                {{ $inactiveAssignedPackages->count() === 1 ? 'One assigned package is inactive.' : $inactiveAssignedPackages->count().' assigned packages are inactive.' }}
                                Inactive packages stay visible here only so admin can review or remove them from this employee venue. They will not appear in active package assignment lists or employee package selection.
                            </div>
                        @endif

                        @if ($packageAssignments->count() > 5)
                            <div class="mt-5 max-w-md">
                                <x-input-label for="package_search" value="Search packages in this venue" />
                                <x-text-input id="package_search" x-model="packageSearch" class="crm-input mt-2 w-full" placeholder="Search by package name or code" />
                            </div>
                        @endif

                        @if ($packageAssignments->isEmpty())
                            <div class="mt-5 rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                                <h3 class="text-xl font-semibold text-slate-950">No packages in this venue yet</h3>
                                <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                    Add a package for this employee in {{ $selectedVenue->name }} before choosing package services.
                                </p>
                            </div>
                        @else
                            <div class="mt-5 crm-table-wrap">
                            <table class="crm-table min-w-[640px] lg:min-w-[680px]">
                                    <thead>
                                        <tr><th>Package</th><th>Services</th><th>Actions</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($packageAssignments as $packageAssignment)
                                            @php($packageNeedle = strtolower(trim(($packageAssignment->package?->name ?? '').' '.($packageAssignment->package?->code ?? ''))))
                                            <tr x-show="!packageSearch || @js($packageNeedle).includes(packageSearch.toLowerCase().trim())">
                                                <td>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <div class="font-semibold text-slate-950">{{ $packageAssignment->package?->name }}</div>
                                                        @if (! ($packageAssignment->package?->is_active ?? false))
                                                            <span class="crm-chip bg-amber-50 text-amber-700">Inactive package</span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">{{ $packageAssignment->package?->code ?: 'No code' }}</div>
                                                </td>
                                                <td><span class="crm-chip bg-cyan-50 text-cyan-700">{{ (int) ($serviceCountsByPackage[$packageAssignment->package_id] ?? 0) }} services</span></td>
                                                <td>
                                                    <div class="flex flex-wrap gap-2">
                                                        <a href="{{ route('admin.master-data.employees.assignments.edit', ['employee' => $employee, 'venue' => $selectedVenue->id, 'package' => $packageAssignment->package_id]) }}" class="crm-button {{ $selectedPackageAssignment && $selectedPackageAssignment->package_id === $packageAssignment->package_id ? 'crm-button-primary' : 'crm-button-secondary' }}">
                                                            {{ $selectedPackageAssignment && $selectedPackageAssignment->package_id === $packageAssignment->package_id ? 'Viewing services' : 'Open services' }}
                                                        </a>
                                                        <form method="POST" action="{{ route('admin.master-data.employees.assignments.packages.destroy', [$employee, $selectedVenue, $packageAssignment->package]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="crm-button crm-button-secondary" onclick="return confirm('Remove this package from the employee venue?')">Remove</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </article>

                <article class="min-w-0 space-y-6">
                    <section class="crm-panel p-6" x-data="{ selectAllVisible: false }">
                        @if ($selectedPackageAssignment)
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="crm-section-title">Services in package</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <h2 class="text-2xl font-semibold text-slate-950">{{ $selectedPackageAssignment->package?->name }}</h2>
                                        @if ($selectedPackageIsInactive)
                                            <span class="crm-chip bg-amber-50 text-amber-700">Inactive package</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Create a new service for this package or attach one of the existing services. This service mapping is specific to the selected employee, venue, and package.
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="crm-button crm-button-primary justify-center disabled:cursor-not-allowed disabled:opacity-50" x-data x-on:click="$dispatch('open-modal', 'create-service-modal')" @disabled($selectedPackageIsInactive)>Create service</button>
                                    <button type="button" class="crm-button crm-button-secondary justify-center disabled:cursor-not-allowed disabled:opacity-50" x-data x-on:click="$dispatch('open-modal', 'assign-service-modal')" @disabled($availableServices->total() === 0 || $selectedPackageIsInactive)>Assign existing service</button>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $selectedPackageServiceCount }} assigned</span>
                                <span class="text-sm text-slate-500">Large packages are paginated so this workspace stays responsive.</span>
                            </div>

                            @if ($selectedPackageIsInactive)
                                <div class="mt-5 rounded-[1.25rem] border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                                    This package is inactive. It remains visible only because it is still assigned to this employee venue. Remove the package assignment if it should no longer be available.
                                </div>
                            @endif

                            @if ($inactiveAssignedServices->isNotEmpty())
                                <div class="mt-5 rounded-[1.25rem] border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                                    {{ $inactiveAssignedServices->count() === 1 ? 'One assigned service is inactive.' : $inactiveAssignedServices->count().' assigned services are inactive.' }}
                                    Inactive services stay visible here only so admin can review or remove them. They will not appear in active service assignment lists or future employee package rows.
                                </div>
                            @endif

                            <form method="GET" action="{{ route('admin.master-data.employees.assignments.edit', $employee) }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                                <input type="hidden" name="venue" value="{{ $selectedVenue->id }}">
                                <input type="hidden" name="package" value="{{ $selectedPackageAssignment->package_id }}">
                                <div class="flex-1 max-w-xl">
                                    <x-input-label for="service_search" value="Search services in this package" />
                                    <x-text-input id="service_search" name="service_search" :value="$selectedPackageServiceFilters['search'] ?? ''" class="crm-input mt-2 w-full" placeholder="Search by service name, code, or notes" />
                                </div>
                                <button type="submit" class="crm-button crm-button-secondary justify-center">Search</button>
                                <a href="{{ route('admin.master-data.employees.assignments.edit', ['employee' => $employee, 'venue' => $selectedVenue->id, 'package' => $selectedPackageAssignment->package_id]) }}" class="crm-button crm-button-secondary justify-center">Reset</a>
                            </form>

                            @if ($serviceAssignments === null || $serviceAssignments->isEmpty())
                                <div class="mt-5 rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                                    <h3 class="text-xl font-semibold text-slate-950">No services in this package yet</h3>
                                    <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                        This package is assigned to the employee, but the employee cannot use it in Function Entry until at least one service is added here.
                                    </p>
                                </div>
                            @else
                                <form method="POST" action="{{ route('admin.master-data.employees.assignments.services.bulk-destroy', [$employee, $selectedVenue, $selectedPackageAssignment->package]) }}" class="mt-5 space-y-4">
                                    @csrf
                                    @method('DELETE')
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600">
                                            <input type="checkbox" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                                   x-on:change="document.querySelectorAll('[data-assigned-service-toggle]').forEach((input) => input.checked = $event.target.checked)">
                                            Select visible page
                                        </label>
                                        <button type="submit" class="crm-button crm-button-secondary justify-center" onclick="return confirm('Remove selected services from this employee package?')">
                                            Remove selected
                                        </button>
                                    </div>

                                <div class="crm-table-wrap">
                                    <table class="crm-table min-w-[760px] lg:min-w-[860px]">
                                        <thead>
                                            <tr><th>Use</th><th>Service</th><th>Mode</th><th>Rate</th><th>Files</th><th>Actions</th></tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($serviceAssignments as $serviceAssignment)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" data-assigned-service-toggle name="service_ids[]" value="{{ $serviceAssignment->service_id }}" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400">
                                                    </td>
                                                    <td>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <div class="font-semibold text-slate-950">{{ $serviceAssignment->service?->name }}</div>
                                                            @if (! ($serviceAssignment->service?->is_active ?? false))
                                                                <span class="crm-chip bg-amber-50 text-amber-700">Inactive service</span>
                                                            @endif
                                                        </div>
                                                        <div class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">{{ $serviceAssignment->service?->code ?: 'No code' }}</div>
                                                    </td>
                                                    <td>{{ $serviceAssignment->service?->personModeLabel() }}</td>
                                                    <td>{{ Money::formatMinor((int) ($serviceAssignment->service?->standard_rate_minor ?? 0)) }}</td>
                                                    <td>
                                                        <span class="crm-chip bg-cyan-50 text-cyan-700">
                                                            {{ $serviceAssignment->service?->attachments?->count() ?? 0 }} files
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <form method="POST" action="{{ route('admin.master-data.employees.assignments.services.destroy', [$employee, $selectedVenue, $selectedPackageAssignment->package, $serviceAssignment->service]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="crm-button crm-button-secondary" onclick="return confirm('Remove this service from the selected employee package?')">Remove</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                    @if ($serviceAssignments->hasPages())
                                        <div>
                                            {{ $serviceAssignments->links() }}
                                        </div>
                                    @endif
                                </form>
                            @endif
                        @else
                            <div class="flex h-full min-h-[22rem] items-center justify-center rounded-[1.75rem] border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                                <div class="max-w-xl">
                                    <p class="crm-section-title">Services</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-slate-950">Select a package first</h2>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        Once a package is selected in the venue section, its service setup opens here so the admin can add or attach services inside that package.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </section>
                </article>
            </section>
        @endif
    </div>

    <x-modal name="create-venue-modal" :show="$errors->createVenue->isNotEmpty()" maxWidth="2xl" focusable>
        <form method="POST" action="{{ route('admin.master-data.employees.assignments.venues.store', $employee) }}" class="space-y-5 p-6">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="crm-section-title">Create venue</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-950">Create and assign a new venue</h2>
                </div>
                <button type="button" class="text-slate-400 transition hover:text-slate-700" x-on:click="$dispatch('close')">Close</button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="create_venue_name" value="Venue name" />
                    <x-text-input id="create_venue_name" name="name" :value="old('name')" class="crm-input mt-2 w-full" />
                    <x-input-error :messages="$errors->createVenue->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="create_venue_code" value="Venue code" />
                    <x-text-input id="create_venue_code" name="code" :value="old('code')" class="crm-input mt-2 w-full" />
                    <x-input-error :messages="$errors->createVenue->get('code')" class="mt-2" />
                </div>
            </div>

            @if ($employee->supportsFrozenFund())
                <div>
                    <x-input-label for="create_venue_frozen_fund" value="Frozen fund" />
                    <x-text-input id="create_venue_frozen_fund" name="frozen_fund" :value="old('frozen_fund', '0.00')" class="crm-input mt-2 w-full" />
                    <x-input-error :messages="$errors->createVenue->get('frozen_fund')" class="mt-2" />
                </div>
            @endif

            <div>
                <p class="crm-section-title">Vendor slots</p>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    @foreach (range(1, 4) as $slotNumber)
                        <div>
                            <x-input-label :for="'vendor_slot_'.$slotNumber" :value="'Vendor slot '.$slotNumber" />
                            <x-text-input :id="'vendor_slot_'.$slotNumber" :name="'vendor_slots['.$slotNumber.']'" :value="old('vendor_slots.'.$slotNumber, 'Vendor '.$slotNumber)" class="crm-input mt-2 w-full" />
                            <x-input-error :messages="$errors->createVenue->get('vendor_slots.'.$slotNumber)" class="mt-2" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="crm-button crm-button-secondary justify-center" x-on:click="$dispatch('close')">Cancel</button>
                <button type="submit" data-loading-label="Creating..." class="crm-button crm-button-primary justify-center">Create venue</button>
            </div>
        </form>
    </x-modal>

    <x-modal name="assign-venue-modal" :show="$errors->attachVenue->isNotEmpty() || request('open_modal') === 'assign-venue-modal'" maxWidth="2xl" focusable>
        <div class="space-y-5 p-6" x-data="{ selectedVenueId: '{{ old('venue_id') }}' }">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="crm-section-title">Assign venue</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-950">Assign an existing venue</h2>
                </div>
                <button type="button" class="text-slate-400 transition hover:text-slate-700" x-on:click="$dispatch('close')">Close</button>
            </div>

            @if ($availableVenues->total() === 0)
                <p class="text-sm leading-6 text-slate-600">All active venues are already assigned to this employee.</p>
            @else
                <form method="GET" action="{{ route('admin.master-data.employees.assignments.edit', $employee) }}" class="space-y-3">
                    <input type="hidden" name="open_modal" value="assign-venue-modal">
                    @if ($selectedVenue)
                        <input type="hidden" name="venue" value="{{ $selectedVenue->id }}">
                    @endif
                    @if ($selectedPackageAssignment)
                        <input type="hidden" name="package" value="{{ $selectedPackageAssignment->package_id }}">
                    @endif
                    <x-input-label for="assign_existing_venue_search" value="Search existing venues" />
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <x-text-input id="assign_existing_venue_search" name="venue_search" :value="$catalogFilters['venue_search'] ?? ''" class="crm-input w-full" placeholder="Search by venue name or code" />
                        <button type="submit" class="crm-button crm-button-secondary justify-center sm:min-w-[7rem]">Search</button>
                    </div>
                </form>

                <p class="text-sm leading-6 text-slate-600">Only 20 venues load at a time.</p>

                <form method="POST" action="{{ route('admin.master-data.employees.assignments.venues.attach', $employee) }}" class="space-y-5">
                    @csrf

                    @if ($employee->supportsFrozenFund())
                        <div>
                            <x-input-label for="attach_venue_frozen_fund" value="Frozen fund" />
                            <x-text-input id="attach_venue_frozen_fund" name="frozen_fund" :value="old('frozen_fund', '0.00')" class="crm-input mt-2 w-full" />
                            <x-input-error :messages="$errors->attachVenue->get('frozen_fund')" class="mt-2" />
                        </div>
                    @endif

                    <div class="max-h-[24rem] space-y-3 overflow-y-auto pr-1">
                        @foreach ($availableVenues as $venue)
                            <label
                                class="block rounded-[1.2rem] border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-300 hover:bg-cyan-50"
                                :class="selectedVenueId === '{{ $venue->id }}' ? 'border-cyan-300 bg-cyan-50 ring-2 ring-cyan-200/70' : ''"
                            >
                                <span class="flex items-start gap-3">
                                    <input type="radio" name="venue_id" value="{{ $venue->id }}" x-model="selectedVenueId" class="mt-1 border-slate-300 text-cyan-600 focus:ring-cyan-400">
                                    <span>
                                        <span class="block font-semibold text-slate-950">{{ $venue->name }}</span>
                                        <span class="mt-1 block text-xs uppercase tracking-[0.16em] text-slate-400">{{ $venue->code ?: 'No code' }}</span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @if ($availableVenues->hasPages())
                        <div class="crm-pagination-shell">
                            {{ $availableVenues->links() }}
                        </div>
                    @endif
                    <x-input-error :messages="$errors->attachVenue->get('venue_id')" class="mt-2" />

                    <div class="flex justify-end gap-3">
                        <button type="button" class="crm-button crm-button-secondary justify-center" x-on:click="$dispatch('close')">Cancel</button>
                        <button type="submit" data-loading-label="Saving..." class="crm-button crm-button-primary justify-center">Assign venue</button>
                    </div>
                </form>
            @endif
        </div>
    </x-modal>

    @if ($selectedVenue)
        <x-modal name="create-package-modal" :show="$errors->createPackage->isNotEmpty()" maxWidth="xl" focusable>
            <form method="POST" action="{{ route('admin.master-data.employees.assignments.packages.store', [$employee, $selectedVenue]) }}" class="space-y-5 p-6">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="crm-section-title">Create package</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Create a package for {{ $selectedVenue->name }}</h2>
                    </div>
                    <button type="button" class="text-slate-400 transition hover:text-slate-700" x-on:click="$dispatch('close')">Close</button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="create_package_name" value="Package name" />
                        <x-text-input id="create_package_name" name="name" :value="old('name')" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->createPackage->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="create_package_code" value="Package code" />
                        <x-text-input id="create_package_code" name="code" :value="old('code')" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->createPackage->get('code')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="create_package_description" value="Description" />
                    <textarea id="create_package_description" name="description" rows="4" class="crm-input mt-2 w-full" placeholder="Optional package notes">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->createPackage->get('description')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" class="crm-button crm-button-secondary justify-center" x-on:click="$dispatch('close')">Cancel</button>
                    <button type="submit" data-loading-label="Creating..." class="crm-button crm-button-primary justify-center">Create package</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="assign-package-modal" :show="$errors->attachPackage->isNotEmpty() || request('open_modal') === 'assign-package-modal'" maxWidth="2xl" focusable>
            <div class="space-y-5 p-6" x-data="{ selectedPackageId: '{{ old('package_id') }}' }">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="crm-section-title">Assign package</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Attach an existing package</h2>
                    </div>
                    <button type="button" class="text-slate-400 transition hover:text-slate-700" x-on:click="$dispatch('close')">Close</button>
                </div>

                @if ($availablePackages->total() === 0)
                    <p class="text-sm leading-6 text-slate-600">All active packages are already attached to this venue for the selected employee.</p>
                @else
                    <form method="GET" action="{{ route('admin.master-data.employees.assignments.edit', $employee) }}" class="space-y-3">
                        <input type="hidden" name="open_modal" value="assign-package-modal">
                        <input type="hidden" name="venue" value="{{ $selectedVenue->id }}">
                        @if ($selectedPackageAssignment)
                            <input type="hidden" name="package" value="{{ $selectedPackageAssignment->package_id }}">
                        @endif
                        <x-input-label for="assign_existing_package_search" value="Search existing packages" />
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <x-text-input id="assign_existing_package_search" name="package_search" :value="$catalogFilters['package_search'] ?? ''" class="crm-input w-full" placeholder="Search by package name, code, or description" />
                            <button type="submit" class="crm-button crm-button-secondary justify-center sm:min-w-[7rem]">Search</button>
                        </div>
                    </form>

                    <p class="text-sm leading-6 text-slate-600">When you attach an existing package, its active mapped services are imported automatically into this employee package. You can then bulk remove only the services you do not want.</p>
                    <p class="text-sm leading-6 text-slate-600">Only 20 packages load at a time.</p>

                    <form method="POST" action="{{ route('admin.master-data.employees.assignments.packages.attach', [$employee, $selectedVenue]) }}" class="space-y-5">
                        @csrf

                        <div class="max-h-[24rem] space-y-3 overflow-y-auto pr-1">
                            @foreach ($availablePackages as $package)
                                <label
                                    class="block rounded-[1.2rem] border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-300 hover:bg-cyan-50"
                                    :class="selectedPackageId === '{{ $package->id }}' ? 'border-cyan-300 bg-cyan-50 ring-2 ring-cyan-200/70' : ''"
                                >
                                    <span class="flex items-start gap-3">
                                        <input type="radio" name="package_id" value="{{ $package->id }}" x-model="selectedPackageId" class="mt-1 border-slate-300 text-cyan-600 focus:ring-cyan-400">
                                        <span>
                                            <span class="block font-semibold text-slate-950">{{ $package->name }}</span>
                                            <span class="mt-1 block text-xs uppercase tracking-[0.16em] text-slate-400">{{ $package->code ?: 'No code' }}</span>
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if ($availablePackages->hasPages())
                            <div class="crm-pagination-shell">
                                {{ $availablePackages->links() }}
                            </div>
                        @endif
                        <x-input-error :messages="$errors->attachPackage->get('package_id')" class="mt-2" />

                        <div class="flex justify-end gap-3">
                            <button type="button" class="crm-button crm-button-secondary justify-center" x-on:click="$dispatch('close')">Cancel</button>
                            <button type="submit" data-loading-label="Saving..." class="crm-button crm-button-primary justify-center">Assign package</button>
                        </div>
                    </form>
                @endif
            </div>
        </x-modal>
    @endif

    @if ($selectedVenue && $selectedPackageAssignment)
        <x-modal name="create-service-modal" :show="$errors->createService->isNotEmpty()" maxWidth="2xl" focusable>
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.master-data.employees.assignments.services.store', [$employee, $selectedVenue, $selectedPackageAssignment->package]) }}" class="space-y-5 p-6" x-data="{ personInputMode: @js(old('person_input_mode', Service::PERSON_MODE_FIXED)) }">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="crm-section-title">Create service</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Create a service in {{ $selectedPackageAssignment->package?->name }}</h2>
                    </div>
                    <button type="button" class="text-slate-400 transition hover:text-slate-700" x-on:click="$dispatch('close')">Close</button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="create_service_name" value="Service name" />
                        <x-text-input id="create_service_name" name="name" :value="old('name')" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->createService->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="create_service_code" value="Service code" />
                        <x-text-input id="create_service_code" name="code" :value="old('code')" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->createService->get('code')" class="mt-2" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="create_service_rate" value="Standard rate" />
                        <x-text-input id="create_service_rate" name="standard_rate" :value="old('standard_rate', '0.00')" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->createService->get('standard_rate')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="create_service_mode" value="Person rule" />
                        <select id="create_service_mode" name="person_input_mode" class="crm-input mt-2 w-full" x-model="personInputMode">
                            <option value="{{ Service::PERSON_MODE_FIXED }}">Person-based service</option>
                            <option value="{{ Service::PERSON_MODE_EMPLOYEE }}">Employee can select persons</option>
                            <option value="{{ Service::PERSON_MODE_NONE }}">Flat-rate service</option>
                        </select>
                        <x-input-error :messages="$errors->createService->get('person_input_mode')" class="mt-2" />
                    </div>
                </div>

                <div x-show="personInputMode === '{{ Service::PERSON_MODE_FIXED }}'" x-cloak>
                    <x-input-label for="create_service_default_persons" value="Default persons" />
                    <x-text-input id="create_service_default_persons" name="default_persons" :value="old('default_persons', 1)" class="crm-input mt-2 w-full" />
                    <x-input-error :messages="$errors->createService->get('default_persons')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="create_service_notes" value="Notes" />
                    <textarea id="create_service_notes" name="notes" rows="4" class="crm-input mt-2 w-full" placeholder="Optional setup notes">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->createService->get('notes')" class="mt-2" />
                </div>

                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                    <x-input-label for="create_service_attachments" value="Reference attachments" />
                    <input
                        id="create_service_attachments"
                        name="attachments[]"
                        type="file"
                        multiple
                        accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.avif,.heic,.heif,.tif,.tiff,.pdf,.doc,.docx,.odt,.xls,.xlsx,.ods,.csv,.odf"
                        class="mt-3 block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
                    />
                    <p class="mt-2 text-xs text-slate-500">Admin-only uploads. Employees can later open or download these files in Function Entry, print, and exports.</p>
                    <p class="mt-2 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
                        Unsupported files such as <span class="font-semibold">.css</span> or <span class="font-semibold">.txt</span> will be rejected.
                    </p>
                    <x-input-error :messages="$errors->createService->get('attachments')" class="mt-2" />
                    <x-input-error :messages="$errors->createService->get('attachments.*')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" class="crm-button crm-button-secondary justify-center" x-on:click="$dispatch('close')">Cancel</button>
                    <button type="submit" data-loading-label="Creating..." class="crm-button crm-button-primary justify-center">Create service</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="assign-service-modal" :show="$errors->attachService->isNotEmpty() || request('open_modal') === 'assign-service-modal'" maxWidth="2xl" focusable>
            <div class="space-y-5 p-6" x-data="{ selectAllVisible: false }">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="crm-section-title">Assign services</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Attach existing services</h2>
                    </div>
                    <button type="button" class="text-slate-400 transition hover:text-slate-700" x-on:click="$dispatch('close')">Close</button>
                </div>

                @if ($availableServices->total() === 0)
                    <p class="text-sm leading-6 text-slate-600">All active services are already attached to this selected employee package.</p>
                @else
                    <form method="GET" action="{{ route('admin.master-data.employees.assignments.edit', $employee) }}" class="space-y-3">
                        <input type="hidden" name="open_modal" value="assign-service-modal">
                        <input type="hidden" name="venue" value="{{ $selectedVenue->id }}">
                        <input type="hidden" name="package" value="{{ $selectedPackageAssignment->package_id }}">
                        <x-input-label for="assign_existing_service_search" value="Search existing services" />
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <x-text-input id="assign_existing_service_search" name="available_service_search" :value="$catalogFilters['available_service_search'] ?? ''" class="crm-input w-full" placeholder="Search by service name, code, or notes" />
                            <button type="submit" class="crm-button crm-button-secondary justify-center sm:min-w-[7rem]">Search</button>
                        </div>
                    </form>

                    <div class="flex items-center justify-between gap-3 rounded-[1.2rem] border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-sm leading-6 text-slate-600">Only 20 services load at a time. Select this page, save, then continue to the next page if needed.</p>
                        <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                            <input type="checkbox" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" x-model="selectAllVisible" x-on:change="document.querySelectorAll('[data-available-service-checkbox]').forEach((checkbox) => checkbox.checked = selectAllVisible)">
                            Select visible page
                        </label>
                    </div>

                    <form method="POST" action="{{ route('admin.master-data.employees.assignments.services.attach', [$employee, $selectedVenue, $selectedPackageAssignment->package]) }}" class="space-y-5">
                        @csrf

                        <div class="grid max-h-[24rem] gap-3 overflow-y-auto pr-1 md:grid-cols-2">
                            @foreach ($availableServices as $service)
                                <label
                                    class="rounded-[1.2rem] border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-cyan-300 hover:bg-cyan-50"
                                >
                                    <span class="flex items-start gap-3">
                                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" data-available-service-checkbox class="mt-1 rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked(collect(old('service_ids', []))->contains($service->id))>
                                        <span>
                                            <span class="block font-semibold text-slate-950">{{ $service->name }}</span>
                                            <span class="mt-1 block text-xs uppercase tracking-[0.16em] text-slate-400">{{ Money::formatMinor($service->standard_rate_minor) }} | {{ strtolower($service->personModeLabel()) }}</span>
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if ($availableServices->hasPages())
                            <div class="crm-pagination-shell">
                                {{ $availableServices->links() }}
                            </div>
                        @endif
                        <x-input-error :messages="$errors->attachService->get('service_ids')" class="mt-2" />

                        <div class="flex justify-end gap-3">
                            <button type="button" class="crm-button crm-button-secondary justify-center" x-on:click="$dispatch('close')">Cancel</button>
                            <button type="submit" data-loading-label="Saving..." class="crm-button crm-button-primary justify-center">Assign services</button>
                        </div>
                    </form>
                @endif
            </div>
        </x-modal>
    @endif
</x-app-layout>
