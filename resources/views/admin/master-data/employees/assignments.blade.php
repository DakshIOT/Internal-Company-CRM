@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Admin Master Data</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Employee setup workspace</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    This is the single place to finish employee access. Venues come first, packages come second, and services are mostly derived from those packages.
                </p>
            </div>
            <a href="{{ route('admin.master-data.employees.edit', $employee) }}" class="crm-button crm-button-secondary justify-center">
                Back to user
            </a>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <form method="POST" action="{{ route('admin.master-data.employees.assignments.update', $employee) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Employee</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ $employee->name }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ $employee->email }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $employee->roleLabel() }}</span>
                    <span class="crm-chip {{ $employee->is_active ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-500' }}">
                        {{ $employee->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">How this works</p>
                <div class="mt-4 grid gap-3 text-sm leading-6 text-slate-600 md:grid-cols-4">
                    <div class="rounded-[1.25rem] bg-slate-50 p-4">1. Keep only the venues this employee should access.</div>
                    <div class="rounded-[1.25rem] bg-slate-50 p-4">2. Choose packages per venue first.</div>
                    <div class="rounded-[1.25rem] bg-slate-50 p-4">3. Package services are added automatically.</div>
                    <div class="rounded-[1.25rem] bg-slate-50 p-4">4. Add only extra services when the package coverage is not enough.</div>
                </div>
                @if ($employee->supportsFrozenFund())
                    <p class="mt-4 text-sm text-slate-600">Frozen fund remains available for each selected venue because this is Employee Type A.</p>
                @endif
            </article>
        </section>

        <section class="space-y-5">
            @foreach ($venues as $venue)
                @php
                    $checked = in_array($venue->id, old('venue_ids', $assignedVenueIds), true);
                    $selectedPackages = collect(old('package_ids_by_venue.'.$venue->id, $packageIdsByVenue[$venue->id] ?? []))
                        ->map(fn ($packageId) => (int) $packageId)
                        ->values();
                    $derivedServiceIds = $selectedPackages
                        ->flatMap(fn ($packageId) => $packageServiceIds[$packageId] ?? [])
                        ->map(fn ($serviceId) => (int) $serviceId)
                        ->unique()
                        ->values();
                    $selectedServices = collect(old('service_ids_by_venue.'.$venue->id, $serviceIdsByVenue[$venue->id] ?? []))
                        ->map(fn ($serviceId) => (int) $serviceId)
                        ->values();
                    $extraServices = $selectedServices
                        ->reject(fn ($serviceId) => $derivedServiceIds->contains($serviceId))
                        ->values();
                @endphp

                <article class="crm-panel p-6">
                    <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="venue_ids[]" value="{{ $venue->id }}" class="h-5 w-5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked($checked)>
                                <div>
                                    <h2 class="text-2xl font-semibold text-slate-950">{{ $venue->name }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">{{ $venue->code ?: 'No code' }}</p>
                                </div>
                            </div>
                            <p class="mt-3 text-sm text-slate-600">Keep this venue enabled only if the employee should be able to log in and work in this venue.</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="crm-chip {{ $venue->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $venue->is_active ? 'Active venue' : 'Inactive venue' }}
                            </span>
                            @if ($employee->supportsFrozenFund())
                                <div class="min-w-[12rem]">
                                    <x-input-label :for="'frozen_fund_'.$venue->id" value="Frozen fund" />
                                    <x-text-input
                                        :id="'frozen_fund_'.$venue->id"
                                        :name="'frozen_funds['.$venue->id.']'"
                                        :value="old('frozen_funds.'.$venue->id, $frozenFunds[$venue->id] ?? Money::formatMinor(0))"
                                        class="crm-input mt-2 w-full"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
                        <section class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="crm-section-title">Packages</p>
                                    <h3 class="mt-1 text-lg font-semibold text-slate-950">Choose packages first</h3>
                                </div>
                                <span class="crm-chip bg-white text-slate-500">{{ $selectedPackages->count() }} selected</span>
                            </div>

                            <div class="crm-table-wrap">
                                <table class="crm-table min-w-[520px]">
                                    <thead>
                                        <tr>
                                            <th>Use</th>
                                            <th>Package</th>
                                            <th>Services</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($packages as $package)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="package_ids_by_venue[{{ $venue->id }}][]" value="{{ $package->id }}" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked($selectedPackages->contains($package->id))>
                                                </td>
                                                <td class="font-semibold text-slate-950">{{ $package->name }}</td>
                                                <td>{{ $package->services->pluck('name')->join(', ') ?: 'No mapped services' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="space-y-4">
                            <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="crm-section-title">Services from packages</p>
                                        <h3 class="mt-1 text-lg font-semibold text-slate-950">Auto-included</h3>
                                    </div>
                                    <span class="crm-chip bg-white text-slate-500">{{ $derivedServiceIds->count() }} included</span>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @forelse ($services->whereIn('id', $derivedServiceIds) as $service)
                                        <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $service->name }}</span>
                                    @empty
                                        <p class="text-sm text-slate-500">No services are being derived yet because no package is selected for this venue.</p>
                                    @endforelse
                                </div>
                            </article>

                            <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="crm-section-title">Extra service access</p>
                                        <h3 class="mt-1 text-lg font-semibold text-slate-950">Only add overrides when needed</h3>
                                    </div>
                                    <span class="crm-chip bg-white text-slate-500">{{ $extraServices->count() }} extra</span>
                                </div>

                                <div class="mt-4 crm-table-wrap">
                                    <table class="crm-table min-w-[420px]">
                                        <thead>
                                            <tr>
                                                <th>Use</th>
                                                <th>Service</th>
                                                <th>Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($services as $service)
                                                @continue($derivedServiceIds->contains($service->id))
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="service_ids_by_venue[{{ $venue->id }}][]" value="{{ $service->id }}" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked($extraServices->contains($service->id))>
                                                    </td>
                                                    <td class="font-semibold text-slate-950">{{ $service->name }}</td>
                                                    <td>{{ Money::formatMinor($service->standard_rate_minor) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                        </section>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.master-data.employees.index') }}" class="crm-button crm-button-secondary justify-center">
                Back to users
            </a>
            <button type="submit" data-loading-label="Saving..." class="crm-button crm-button-primary justify-center">
                Save employee setup
            </button>
        </div>
    </form>
</x-app-layout>
