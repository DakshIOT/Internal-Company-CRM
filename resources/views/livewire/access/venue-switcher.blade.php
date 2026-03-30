<div class="w-full sm:w-auto">
    <form wire:submit.prevent="apply" class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <label for="venue-switcher" class="text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-slate-500">
            Active venue
        </label>

        <div class="flex items-center gap-2">
            <select
                id="venue-switcher"
                wire:model="selectedVenueId"
                class="min-w-[13rem] rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm focus:border-cyan-400 focus:ring-cyan-400"
            >
                <option value="">Choose venue</option>
                @foreach ($venues as $venue)
                    <option value="{{ $venue['id'] }}">{{ $venue['name'] }}</option>
                @endforeach
            </select>

            <button type="submit" class="crm-button crm-button-secondary whitespace-nowrap">
                Switch
            </button>
        </div>

        @error('selectedVenueId')
            <p class="text-xs font-medium text-rose-500">{{ $message }}</p>
        @enderror
    </form>
</div>
