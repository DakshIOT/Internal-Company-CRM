@php
    $isEditing = $isEditing ?? false;
@endphp

<div class="space-y-6">
    <article class="crm-panel p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="crm-section-title">Base function details</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ $isEditing ? 'Edit base details' : 'Create a function entry' }}</h2>
            </div>
            <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $currentVenue->name }}</span>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="entry_date" value="Date" />
                <x-text-input id="entry_date" name="entry_date" type="date" :value="old('entry_date', optional($functionEntry->entry_date)->format('Y-m-d') ?? now()->toDateString())" class="crm-input mt-2 w-full" />
                <x-input-error :messages="$errors->get('entry_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="name" value="Function name" />
                <x-text-input id="name" name="name" :value="old('name', $functionEntry->name)" class="crm-input mt-2 w-full" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="notes" value="Notes" />
                <textarea id="notes" name="notes" rows="5" class="crm-input mt-2 w-full">{{ old('notes', $functionEntry->notes) }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </div>
    </article>

    <article class="crm-panel p-6">
        <p class="crm-section-title">Base attachments</p>
        <div class="mt-4">
            @include('employee.functions.partials.attachments', [
                'attachable' => $functionEntry,
                'functionEntry' => $functionEntry,
                'inputId' => 'base_attachments',
                'emptyMessage' => 'No base attachments uploaded yet.',
            ])
        </div>
    </article>

    <div class="flex flex-col gap-3 sm:flex-row">
        <button type="submit" class="crm-button crm-button-primary justify-center">
            {{ $isEditing ? 'Save base details' : 'Create function entry' }}
        </button>
        <a href="{{ route('employee.functions.index') }}" class="crm-button crm-button-secondary justify-center">
            Back to function entries
        </a>
    </div>
</div>
