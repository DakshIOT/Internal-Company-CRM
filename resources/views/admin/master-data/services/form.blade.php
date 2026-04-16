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

    <form method="POST" enctype="multipart/form-data" action="{{ $isEditing ? route('admin.master-data.services.update', $service) : route('admin.master-data.services.store') }}" class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="space-y-6" x-data="{ personInputMode: @js(old('person_input_mode', $service->person_input_mode ?? 'fixed')) }">
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

                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4">
                        <p class="crm-section-title">Quantity rule</p>
                        <div class="mt-4 space-y-3">
                            <label class="flex items-start gap-3 rounded-[1.1rem] border border-slate-200 bg-white px-4 py-3">
                                <input
                                    type="radio"
                                    name="person_input_mode"
                                    value="fixed"
                                    class="mt-1 border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                    x-model="personInputMode"
                                >
                                <span>
                                    <span class="block font-semibold text-slate-900">Person-based service</span>
                                    <span class="mt-1 block text-sm leading-6 text-slate-500">Admin sets the fixed person count. Employee cannot change it later.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-[1.1rem] border border-slate-200 bg-white px-4 py-3">
                                <input
                                    type="radio"
                                    name="person_input_mode"
                                    value="employee"
                                    class="mt-1 border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                    x-model="personInputMode"
                                >
                                <span>
                                    <span class="block font-semibold text-slate-900">Employee can select persons</span>
                                    <span class="mt-1 block text-sm leading-6 text-slate-500">The persons input appears in function entry and the employee can type the count there.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-[1.1rem] border border-slate-200 bg-white px-4 py-3">
                                <input
                                    type="radio"
                                    name="person_input_mode"
                                    value="none"
                                    class="mt-1 border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                    x-model="personInputMode"
                                >
                                <span>
                                    <span class="block font-semibold text-slate-900">Flat-rate service</span>
                                    <span class="mt-1 block text-sm leading-6 text-slate-500">No persons field in employee workflow. Employee will only handle extra charge and notes.</span>
                                </span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('person_input_mode')" class="mt-2" />
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4" x-show="personInputMode === 'fixed'" x-cloak>
                        <x-input-label for="default_persons" value="Fixed persons" />
                        <x-text-input
                            id="default_persons"
                            name="default_persons"
                            type="number"
                            min="1"
                            :value="old('default_persons', $service->default_persons ?? 1)"
                            class="crm-input mt-2 w-full"
                        />
                        <x-input-error :messages="$errors->get('default_persons')" class="mt-2" />
                        <p class="mt-3 text-sm leading-6 text-slate-500">This number is locked in for employees when the service appears inside a package.</p>
                    </div>
                </div>

                <div class="mt-5">
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="5" class="crm-input mt-2 w-full">{{ old('notes', $service->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </article>

            <article class="crm-panel p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="crm-section-title">Package linkage</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Assign this service to packages</h2>
                    </div>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ count(old('package_ids', $selectedPackageIds)) }} selected</span>
                </div>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Pick any packages that should include this service by default. Admin still controls employee access later from the employee setup workspace.
                </p>

                <div class="mt-5 crm-table-wrap">
                    <table class="crm-table min-w-[680px]">
                        <thead>
                            <tr>
                                <th>Use</th>
                                <th>Package</th>
                                <th>Code</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($packages as $package)
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            name="package_ids[]"
                                            value="{{ $package->id }}"
                                            class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                            @checked(in_array($package->id, old('package_ids', $selectedPackageIds), true))
                                        >
                                    </td>
                                    <td class="font-semibold text-slate-950">{{ $package->name }}</td>
                                    <td>{{ $package->code ?: 'No code' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-sm text-slate-500">
                                        Create packages first, then return here to connect this service.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-input-error :messages="$errors->get('package_ids')" class="mt-3" />
                <x-input-error :messages="$errors->get('package_ids.*')" class="mt-3" />
            </article>

            <article class="crm-panel p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="crm-section-title">Reference attachments</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Admin-only service files</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Upload brochures, setup notes, sample layouts, or supporting files for this service. Employees can open and download these files later inside Function Entry, print sheets, and exports.
                        </p>
                    </div>
                    @if ($isEditing)
                        <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $service->attachments->count() }} files</span>
                    @endif
                </div>

                <div class="mt-5">
                    @include('ledgers.partials.attachments', [
                        'entry' => $service,
                        'routeKey' => 'service',
                        'previewRoute' => 'admin.master-data.services.attachments.preview',
                        'downloadRoute' => 'admin.master-data.services.attachments.download',
                        'destroyRoute' => 'admin.master-data.services.attachments.destroy',
                        'deleteFormIdPrefix' => 'attachment-delete',
                        'allowDelete' => $isEditing,
                        'showUpload' => true,
                        'inputId' => 'service_attachments',
                        'emptyMessage' => $isEditing ? 'No admin reference files attached to this service yet.' : null,
                    ])
                </div>
            </article>
        </section>

        <aside class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Guidance</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <li>Keep names short enough to fit cleanly in mobile function-entry rows later.</li>
                    <li>Rates are stored as integer minor units even though forms show plain decimals.</li>
                        <li>Choose person-based if the line should multiply by a fixed admin-defined person count.</li>
                        <li>Choose employee-select if the employee should type the person count during function entry.</li>
                        <li>Choose flat-rate if employees should not see any persons field for this service.</li>
                    <li>Package selection here only defines where this service appears by default.</li>
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

    @if ($isEditing)
        @foreach ($service->attachments as $attachment)
            <form id="attachment-delete-{{ $attachment->id }}" method="POST" action="{{ route('admin.master-data.services.attachments.destroy', ['service' => $service, 'attachment' => $attachment]) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
</x-app-layout>
