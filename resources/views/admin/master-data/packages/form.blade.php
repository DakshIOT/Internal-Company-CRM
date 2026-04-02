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

    <form method="POST" action="{{ $isEditing ? route('admin.master-data.packages.update', $package) : route('admin.master-data.packages.store') }}" class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="space-y-6">
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
        </section>

        <aside class="space-y-6">
            <article class="crm-panel p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="crm-section-title">Service mapping</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Included services</h2>
                    </div>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ count(old('service_ids', $selectedServiceIds)) }} selected</span>
                </div>

                <div class="mt-6 crm-table-wrap">
                    <table class="crm-table min-w-[720px]">
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
                            @forelse ($services as $service)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked(in_array($service->id, old('service_ids', $selectedServiceIds), true))>
                                    </td>
                                    <td class="font-semibold text-slate-900">{{ $service->name }}</td>
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
                                        Create services first, then come back to map them into packages.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Assignment note</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Packages are definitions only here. Once the package is saved, assign it to employees per venue from the employee setup workspace so the admin only has one access-management flow to remember.
                </p>
            </article>

            <div class="flex flex-col gap-3">
                <button type="submit" data-loading-label="{{ $isEditing ? 'Saving...' : 'Creating...' }}" class="crm-button crm-button-primary justify-center">
                    {{ $isEditing ? 'Save package' : 'Create package' }}
                </button>
                <a href="{{ route('admin.master-data.packages.index') }}" class="crm-button crm-button-secondary justify-center">
                    Back to packages
                </a>
            </div>
        </aside>
    </form>
</x-app-layout>
