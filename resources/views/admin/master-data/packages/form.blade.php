@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="crm-section-title">Admin Master Data</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">
                {{ $isEditing ? 'Edit Package' : 'Create Package' }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Build package identity on the left and map service rows with ordering on the right.
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
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Available services</h2>
                    </div>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ count(old('service_ids', $selectedServiceIds)) }} selected</span>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($services as $service)
                        <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4">
                            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" class="mt-1 rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked(in_array($service->id, old('service_ids', $selectedServiceIds), true))>
                                    <span>
                                        <span class="block font-semibold text-slate-900">{{ $service->name }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ $service->code ?: 'No code' }} · {{ Money::formatMinor($service->standard_rate_minor) }}</span>
                                    </span>
                                </label>
                                <div class="w-full md:w-28">
                                    <x-input-label :for="'sort_order_'.$service->id" value="Order" />
                                    <x-text-input
                                        :id="'sort_order_'.$service->id"
                                        :name="'sort_orders['.$service->id.']'"
                                        :value="old('sort_orders.'.$service->id, $sortOrders[$service->id] ?? '')"
                                        class="crm-input mt-2 w-full"
                                    />
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                            Create services first, then come back to map them into packages.
                        </div>
                    @endforelse
                </div>
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
