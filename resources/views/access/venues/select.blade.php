<x-guest-layout>
    <div class="space-y-2">
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Venue selection</p>
        <h3 class="text-3xl font-semibold tracking-tight text-slate-950">Choose a venue</h3>
        <p class="text-sm leading-6 text-slate-600">This selection sets the full employee workspace.</p>
    </div>

    @if ($venues->isEmpty())
        <div class="mt-8 rounded-[1.75rem] border border-amber-200 bg-amber-50 p-6">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-700">No assigned venue</p>
            <p class="mt-3 text-sm leading-6 text-amber-900">
                Your account does not have an active venue assignment yet. Contact the admin team before proceeding.
            </p>
        </div>
    @else
        <div class="mt-8 rounded-[1.85rem] border border-slate-200 bg-slate-50 p-4 sm:p-5">
            <form method="POST" action="{{ route('venues.store') }}" class="space-y-4">
                @csrf

                <div class="space-y-3">
                    @foreach ($venues as $venue)
                        <label class="group block cursor-pointer">
                            <input
                                type="radio"
                                name="venue_id"
                                value="{{ $venue->id }}"
                                class="peer sr-only"
                                @checked(old('venue_id', $selectedVenueId) == $venue->id)
                            >
                            <div class="flex items-center justify-between gap-4 rounded-[1.35rem] border border-slate-200 bg-white px-4 py-4 shadow-sm transition peer-checked:border-cyan-400 peer-checked:bg-cyan-50 peer-checked:shadow-[0_18px_40px_-28px_rgba(8,145,178,0.45)] group-hover:border-slate-300">
                                <div>
                                    <p class="text-base font-semibold text-slate-950">{{ $venue->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">Open this venue workspace</p>
                                </div>
                                <span class="crm-chip bg-slate-100 text-slate-500 peer-checked:bg-cyan-100 peer-checked:text-cyan-700">
                                    Select
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>

                <x-input-error :messages="$errors->get('venue_id')" class="mt-2" />

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm leading-6 text-slate-600">You can switch venues later from the top bar.</p>
                    <button type="submit" class="crm-button crm-button-primary justify-center sm:min-w-[12rem]">
                        Continue
                    </button>
                </div>
            </form>
        </div>
    @endif
</x-guest-layout>
