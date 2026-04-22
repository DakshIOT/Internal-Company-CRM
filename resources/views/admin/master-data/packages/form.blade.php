@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="crm-section-title">Admin Master Data</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">
                {{ $isEditing ? 'Edit Package' : 'Create Package' }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Define the package here and map the included services. Employee access to this package is managed later from the employee setup workspace.
            </p>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
    <form
        method="POST"
        action="{{ $isEditing ? route('admin.master-data.packages.update', $package) : route('admin.master-data.packages.store') }}"
        class="space-y-6"
        @if (! $isEditing) id="package-create-form" @endif
    >
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <article class="crm-panel p-6">
            <p class="crm-section-title">Package identity</p>
            <div class="mt-6 grid gap-5">
                <div>
                    <x-input-label for="name" value="Package name" />
                    <x-text-input id="name" name="name" :value="old('name', $package->name)" class="crm-input mt-2 w-full" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="code" value="Code" />
                    <x-text-input id="code" name="code" :value="old('code', $package->code)" class="crm-input mt-2 w-full" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="6" class="crm-input mt-2 w-full">{{ old('description', $package->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked(old('is_active', $package->is_active ?? true))>
                    Keep this package active
                </label>
            </div>
        </article>

        <div class="flex flex-col gap-3">
            <button type="submit" data-loading-label="{{ $isEditing ? 'Saving...' : 'Creating...' }}" class="crm-button crm-button-primary justify-center">
                {{ $isEditing ? 'Save package details' : 'Create package' }}
            </button>
            <a href="{{ route('admin.master-data.packages.index') }}" class="crm-button crm-button-secondary justify-center">
                Back to packages
            </a>
        </div>
    </form>

        <aside class="space-y-6 min-w-0">
            @if (! $isEditing)
                <article class="crm-panel p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="crm-section-title">Service mapping</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-950">Included services</h2>
                        </div>
                        <span class="crm-chip bg-cyan-50 text-cyan-700">{{ count(old('service_ids', [])) }} selected</span>
                    </div>

                    <form method="GET" action="{{ route('admin.master-data.packages.create') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <x-input-label for="service_search" value="Search services" />
                            <x-text-input id="service_search" name="service_search" :value="$serviceFilters['search'] ?? ''" class="crm-input mt-2 w-full" placeholder="Search by service name, code, or notes" />
                        </div>
                        <button type="submit" class="crm-button crm-button-secondary justify-center">Search</button>
                        <a href="{{ route('admin.master-data.packages.create') }}" class="crm-button crm-button-secondary justify-center">Reset</a>
                    </form>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm leading-6 text-slate-600">
                            Only 20 services load at a time so large catalogs do not freeze the page. You can create the package with the selected visible services and continue editing later if needed.
                        </p>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600" x-data="{ checked: false }">
                            <input type="checkbox" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                   x-on:change="document.querySelectorAll('[data-create-service-toggle]').forEach((input) => input.checked = $event.target.checked)">
                            Select visible page
                        </label>
                    </div>

                    <div class="mt-5 crm-table-wrap">
                        <table class="crm-table min-w-[760px]">
                            <thead>
                                <tr>
                                    <th>Use</th>
                                    <th>Service</th>
                                    <th>Code</th>
                                    <th>Rate</th>
                                    <th>Order</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($serviceCatalog as $service)
                                    <tr>
                                        <td>
                                            <input
                                                type="checkbox"
                                                data-create-service-toggle
                                                name="service_ids[]"
                                                value="{{ $service->id }}"
                                                form="package-create-form"
                                                class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                                @checked(in_array($service->id, old('service_ids', []), true))
                                            >
                                        </td>
                                        <td class="font-semibold text-slate-900">{{ $service->name }}</td>
                                        <td>{{ $service->code ?: 'No code' }}</td>
                                        <td>{{ Money::formatMinor($service->standard_rate_minor) }}</td>
                                        <td class="w-28">
                                            <x-text-input
                                                :id="'sort_order_'.$service->id"
                                                :name="'sort_orders['.$service->id.']'"
                                                :value="old('sort_orders.'.$service->id, '')"
                                                form="package-create-form"
                                                class="crm-input w-full"
                                                placeholder="1"
                                            />
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-sm text-slate-500">
                                            No services match this search.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($serviceCatalog && $serviceCatalog->hasPages())
                        <div class="mt-5">
                            {{ $serviceCatalog->links() }}
                        </div>
                    @endif
                </article>
            @else
            <article class="crm-panel p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="crm-section-title">Service mapping</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Included services</h2>
                    </div>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $mappedServicesCount }} mapped</span>
                </div>

                <form method="GET" action="{{ route('admin.master-data.packages.edit', $package) }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <x-input-label for="service_search" value="Search services" />
                        <x-text-input id="service_search" name="service_search" :value="$serviceFilters['search'] ?? ''" class="crm-input mt-2 w-full" placeholder="Search by service name, code, or notes" />
                    </div>
                    <button type="submit" class="crm-button crm-button-secondary justify-center">Search</button>
                    <a href="{{ route('admin.master-data.packages.edit', $package) }}" class="crm-button crm-button-secondary justify-center">Reset</a>
                </form>

                <form method="POST" action="{{ route('admin.master-data.packages.mapping.update', $package) }}" class="mt-5 space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="service_search" value="{{ $serviceFilters['search'] ?? '' }}">
                    <input type="hidden" name="service_page" value="{{ $serviceCatalog?->currentPage() }}">

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm leading-6 text-slate-600">
                            Only 20 services load at a time. Save the current page after checking or unchecking services.
                        </p>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600" x-data="{ checked: false }">
                            <input type="checkbox" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                   x-on:change="document.querySelectorAll('[data-service-toggle]').forEach((input) => input.checked = $event.target.checked)">
                            Select visible page
                        </label>
                    </div>

                    <div class="crm-table-wrap">
                        <table class="crm-table min-w-[760px]">
                            <thead>
                                <tr>
                                    <th>Use</th>
                                    <th>Service</th>
                                    <th>Code</th>
                                    <th>Rate</th>
                                    <th>Order</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($serviceCatalog as $service)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="visible_service_ids[]" value="{{ $service->id }}">
                                            <input
                                                type="checkbox"
                                                data-service-toggle
                                                name="service_ids[]"
                                                value="{{ $service->id }}"
                                                class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                                @checked(in_array($service->id, old('service_ids', $selectedServiceIds), true))
                                            >
                                        </td>
                                        <td>
                                            <div class="font-semibold text-slate-900">{{ $service->name }}</div>
                                            @if (! $service->is_active)
                                                <div class="mt-1 text-xs uppercase tracking-[0.16em] text-amber-600">Inactive service kept for existing mappings</div>
                                            @endif
                                        </td>
                                        <td>{{ $service->code ?: 'No code' }}</td>
                                        <td>{{ Money::formatMinor($service->standard_rate_minor) }}</td>
                                        <td class="w-28">
                                            <x-text-input
                                                :id="'sort_order_'.$service->id"
                                                :name="'sort_orders['.$service->id.']'"
                                                :value="old('sort_orders.'.$service->id, $sortOrders[$service->id] ?? '')"
                                                class="crm-input w-full"
                                                placeholder="1"
                                            />
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-sm text-slate-500">
                                            No services match this search.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($serviceCatalog && $serviceCatalog->hasPages())
                        <div>
                            {{ $serviceCatalog->links() }}
                        </div>
                    @endif

                    <div class="flex justify-end">
                        <button type="submit" data-loading-label="Saving..." class="crm-button crm-button-primary justify-center">
                            Save visible page mapping
                        </button>
                    </div>
                </form>
            </article>
            @endif

            <article class="crm-panel p-6">
                <p class="crm-section-title">Assignment note</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Packages are definitions only here. Once the package is saved, assign it to employees per venue from the employee setup workspace so the admin only has one access-management flow to remember.
                </p>
            </article>

        </aside>
    </div>
</x-app-layout>
