@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="crm-section-title">Admin Master Data</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">
                {{ $isEditing ? 'Edit Service' : 'Create Service' }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Service rates become the default baseline for package rows and later function-entry line items.
            </p>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <form method="POST" action="{{ $isEditing ? route('admin.master-data.services.update', $service) : route('admin.master-data.services.store') }}" class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="space-y-6">
            <article class="crm-panel p-6">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="name" value="Service name" />
                        <x-text-input id="name" name="name" :value="old('name', $service->name)" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="code" value="Code" />
                        <x-text-input id="code" name="code" :value="old('code', $service->code)" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="standard_rate" value="Standard rate" />
                        <x-text-input id="standard_rate" name="standard_rate" :value="old('standard_rate', isset($service->standard_rate_minor) ? Money::formatMinor($service->standard_rate_minor) : '0.00')" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->get('standard_rate')" class="mt-2" />
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked(old('is_active', $service->is_active ?? true))>
                            Keep this service active
                        </label>
                    </div>
                </div>

                <div class="mt-5">
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="5" class="crm-input mt-2 w-full">{{ old('notes', $service->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </article>
        </section>

        <aside class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Guidance</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <li>Keep names short enough to fit cleanly in mobile function-entry rows later.</li>
                    <li>Rates are stored as integer minor units even though forms show plain decimals.</li>
                </ul>
            </article>

            @if ($isEditing)
                <article class="crm-panel p-6">
                    <p class="crm-section-title">Current usage</p>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <p><span class="font-semibold text-slate-900">{{ $service->packages_count }}</span> mapped packages</p>
                        <p><span class="font-semibold text-slate-900">{{ $service->assignments_count }}</span> employee assignments</p>
                    </div>
                </article>
            @endif

            <div class="flex flex-col gap-3">
                <button type="submit" data-loading-label="{{ $isEditing ? 'Saving...' : 'Creating...' }}" class="crm-button crm-button-primary justify-center">
                    {{ $isEditing ? 'Save service' : 'Create service' }}
                </button>
                <a href="{{ route('admin.master-data.services.index') }}" class="crm-button crm-button-secondary justify-center">
                    Back to services
                </a>
            </div>
        </aside>
    </form>
</x-app-layout>
