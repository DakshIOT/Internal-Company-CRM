@php
    use App\Support\Money;

    $routeKey = $entryRouteParameter ?? 'entry';
    $previewRoute = $attachmentRoutes['preview'] ?? null;
    $downloadRoute = $attachmentRoutes['download'] ?? null;
    $destroyRoute = $attachmentRoutes['destroy'] ?? null;
    $indexRoute = $indexRoute ?? 'employee.'.$moduleSlug.'.index';
    $submitLabel = $submitLabel ?? ($isEditing ? 'Save '.$moduleLabel.' Entry' : 'Create '.$moduleLabel.' Entry');
@endphp

<div class="crm-panel p-6">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div>
            <x-input-label for="entry_date" value="Date" />
            <x-text-input id="entry_date" name="entry_date" type="date" :value="old('entry_date', optional($entry->entry_date)->format('Y-m-d'))" class="crm-input mt-2 w-full" />
            <x-input-error :messages="$errors->get('entry_date')" class="mt-2" />
        </div>

        @if (($vendorOptions ?? collect())->isNotEmpty())
            <div>
                <x-input-label for="venue_vendor_id" value="Vendor" />
                <select id="venue_vendor_id" name="venue_vendor_id" class="crm-input mt-2 w-full">
                    <option value="">Select a vendor</option>
                    @foreach ($vendorOptions as $vendor)
                        <option value="{{ $vendor->id }}" @selected((int) old('venue_vendor_id', $entry->venue_vendor_id) === (int) $vendor->id)>
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('venue_vendor_id')" class="mt-2" />
            </div>
        @endif

        <div class="{{ ($vendorOptions ?? collect())->isNotEmpty() ? '' : 'xl:col-span-2' }}">
            <x-input-label for="name" value="Name" />
            <x-text-input id="name" name="name" :value="old('name', $entry->name)" class="crm-input mt-2 w-full" placeholder="Entry name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="amount" value="Amount" />
            <x-text-input id="amount" name="amount" :value="old('amount', Money::formatMinor($entry->amount_minor))" class="crm-input mt-2 w-full" placeholder="Amount" />
            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
        </div>
    </div>

    <div class="mt-5">
        <x-input-label for="notes" value="Notes" />
        <textarea id="notes" name="notes" rows="4" class="crm-input mt-2 w-full" placeholder="Notes">{{ old('notes', $entry->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="mt-6">
        @include('ledgers.partials.attachments', [
            'entry' => $entry,
            'routeKey' => $routeKey,
            'previewRoute' => $previewRoute,
            'downloadRoute' => $downloadRoute,
            'destroyRoute' => $destroyRoute,
            'inputId' => $inputId ?? 'attachments',
            'showUpload' => true,
            'allowDelete' => $isEditing ?? false,
            'emptyMessage' => $isEditing ? 'No files linked to this entry yet.' : null,
        ])
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
        <button type="submit" data-loading-label="{{ $isEditing ? 'Saving...' : 'Creating...' }}" class="crm-button crm-button-primary justify-center">
            {{ $submitLabel }}
        </button>
        <a href="{{ route($indexRoute) }}" class="crm-button crm-button-secondary justify-center">
            Back to list
        </a>
    </div>
</div>
