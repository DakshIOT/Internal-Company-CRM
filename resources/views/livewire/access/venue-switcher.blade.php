<div class="w-full">
    <div class="crm-context-switcher">
        <form method="POST" action="{{ route('venues.switch') }}" class="crm-context-switcher-form">
            @csrf
            <label for="venue-switcher" class="crm-context-switcher-label">
                Active venue
            </label>

            <div class="crm-context-switcher-controls min-w-0 flex-1">
                <select
                    id="venue-switcher"
                    name="venue_id"
                    class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-cyan-400 focus:ring-cyan-400 sm:min-w-[12rem]"
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
</div>
