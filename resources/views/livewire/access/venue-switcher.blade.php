<div class="w-full sm:w-auto">
    <form method="POST" action="{{ route('venues.switch') }}" class="flex flex-col gap-1.5 md:flex-row md:items-center">
        @csrf
        <label for="venue-switcher" class="text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-slate-500">
            Active venue
        </label>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <select
                id="venue-switcher"
                name="venue_id"
                class="min-w-[11rem] rounded-2xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-cyan-400 focus:ring-cyan-400"
            >
                <option value="">Choose venue</option>
                @foreach ($venues as $venue)
                    <option value="{{ $venue['id'] }}" @selected((int) $selectedVenueId === (int) $venue['id'])>{{ $venue['name'] }}</option>
                @endforeach
            </select>

            <button type="submit" data-loading-label="Switching..." class="crm-button crm-button-secondary w-full justify-center whitespace-nowrap px-4 py-2 sm:w-auto">
                Switch
            </button>
        </div>

        @error('venue_id')
            <p class="text-xs font-medium text-rose-500">{{ $message }}</p>
        @enderror
    </form>
</div>
