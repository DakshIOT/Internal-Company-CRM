@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Admin Master Data</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Assignment workspace</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Control what {{ $employee->name }} can access by venue, and define frozen fund only when the role supports it.
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

        <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <article class="crm-panel p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="crm-section-title">Employee summary</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ $employee->name }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ $employee->email }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $employee->roleLabel() }}</span>
                        <span class="crm-chip {{ $employee->is_active ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-500' }}">
                            {{ $employee->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Assignment rules</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <li>Only assigned venues can appear in employee workflows.</li>
                    <li>Service and package availability is stored per employee and per venue.</li>
                    <li>Frozen fund is accepted only for Employee Type A.</li>
                </ul>
            </article>
        </section>

        <section class="crm-panel p-6">
            <p class="crm-section-title">Venue assignments</p>
            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                @foreach ($venues as $venue)
                    @php $checked = in_array($venue->id, old('venue_ids', $assignedVenueIds), true); @endphp
                    <label class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" name="venue_ids[]" value="{{ $venue->id }}" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked($checked)>
                                    <span class="text-lg font-semibold text-slate-950">{{ $venue->name }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">{{ $venue->code ?: 'No code' }}</p>
                            </div>
                            <span class="crm-chip {{ $venue->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $venue->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        @if ($employee->supportsFrozenFund())
                            <div class="mt-4">
                                <x-input-label :for="'frozen_fund_'.$venue->id" value="Frozen fund" />
                                <x-text-input
                                    :id="'frozen_fund_'.$venue->id"
                                    :name="'frozen_funds['.$venue->id.']'"
                                    :value="old('frozen_funds.'.$venue->id, $frozenFunds[$venue->id] ?? Money::formatMinor(0))"
                                    class="crm-input mt-2 w-full"
                                />
                            </div>
                        @endif
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('venue_ids.*')" class="mt-3" />
        </section>

        <section class="crm-panel p-6">
            <p class="crm-section-title">Service availability</p>
            <div class="mt-6 space-y-4">
                @foreach ($venues as $venue)
                    @php $selectedServices = old('service_ids_by_venue.'.$venue->id, $serviceIdsByVenue[$venue->id] ?? []); @endphp
                    <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-slate-950">{{ $venue->name }}</h2>
                            <span class="crm-chip bg-white text-slate-500">Services</span>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($services as $service)
                                <label class="flex items-start gap-3 rounded-2xl border border-white bg-white p-4 shadow-sm">
                                    <input type="checkbox" name="service_ids_by_venue[{{ $venue->id }}][]" value="{{ $service->id }}" class="mt-1 rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked(in_array($service->id, $selectedServices, true))>
                                    <span>
                                        <span class="block font-semibold text-slate-900">{{ $service->name }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ Money::formatMinor($service->standard_rate_minor) }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="crm-panel p-6">
            <p class="crm-section-title">Package availability</p>
            <div class="mt-6 space-y-4">
                @foreach ($venues as $venue)
                    @php $selectedPackages = old('package_ids_by_venue.'.$venue->id, $packageIdsByVenue[$venue->id] ?? []); @endphp
                    <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-slate-950">{{ $venue->name }}</h2>
                            <span class="crm-chip bg-white text-slate-500">Packages</span>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($packages as $package)
                                <label class="flex items-start gap-3 rounded-2xl border border-white bg-white p-4 shadow-sm">
                                    <input type="checkbox" name="package_ids_by_venue[{{ $venue->id }}][]" value="{{ $package->id }}" class="mt-1 rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked(in_array($package->id, $selectedPackages, true))>
                                    <span>
                                        <span class="block font-semibold text-slate-900">{{ $package->name }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ $package->services->count() }} services mapped</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.master-data.employees.index') }}" class="crm-button crm-button-secondary justify-center">
                Back to users
            </a>
            <button type="submit" class="crm-button crm-button-primary justify-center">
                Save assignments
            </button>
        </div>
    </form>
</x-app-layout>
