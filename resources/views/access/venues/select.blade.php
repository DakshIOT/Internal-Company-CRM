<x-guest-layout>
    <div class="space-y-2">
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Venue selection</p>
        <h3 class="text-3xl font-semibold tracking-tight text-slate-950">Choose your workspace</h3>
        <p class="text-sm leading-6 text-slate-600">
            Your venue choice controls the entire employee dashboard context. You can switch later from the top bar.
        </p>
    </div>

    @if ($venues->isEmpty())
        <div class="mt-8 rounded-[1.75rem] border border-amber-200 bg-amber-50 p-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-700">No assigned venue</p>
            <p class="mt-3 text-sm leading-6 text-amber-900">
                Your account does not have an active venue assignment yet. Contact the admin team before proceeding.
            </p>
        </div>
    @else
        <form method="POST" action="{{ route('venues.store') }}" class="mt-8">
            @csrf

            <div class="grid gap-4">
                @foreach ($venues as $venue)
                    <label class="group cursor-pointer">
                        <input
                            type="radio"
                            name="venue_id"
                            value="{{ $venue->id }}"
                            class="peer sr-only"
                            @checked(old('venue_id', $selectedVenueId) == $venue->id)
                        >
                        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm transition peer-checked:border-cyan-400 peer-checked:bg-cyan-50 peer-checked:shadow-[0_18px_40px_-28px_rgba(8,145,178,0.6)] group-hover:border-slate-300">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Assigned venue</p>
                                    <h4 class="mt-2 text-xl font-semibold text-slate-950">{{ $venue->name }}</h4>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        Enter the CRM with this venue as the active context for all employee-facing data.
                                    </p>
                                </div>
                                <span class="crm-chip bg-slate-100 text-slate-500 peer-checked:bg-cyan-100 peer-checked:text-cyan-700">
                                    Select
                                </span>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            <x-input-error :messages="$errors->get('venue_id')" class="mt-4" />

            <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm leading-6 text-slate-600">
                    Venue switching stays available later from the top bar.
                </p>
                <button type="submit" class="crm-button crm-button-primary justify-center sm:min-w-[12rem]">
                    Enter workspace
                </button>
            </div>
        </form>
    @endif
</x-guest-layout>
