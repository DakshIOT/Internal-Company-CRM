<?php

namespace App\Http\Livewire\Access;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VenueSwitcher extends Component
{
    public array $venues = [];
    public ?int $selectedVenueId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->isEmployee()) {
            return;
        }

        $this->venues = $user->venues()
            ->active()
            ->orderBy('name')
            ->get(['venues.id', 'venues.name'])
            ->map(fn ($venue) => ['id' => $venue->id, 'name' => $venue->name])
            ->all();

        $selectedVenueId = session('selected_venue_id');
        $this->selectedVenueId = $selectedVenueId ? (int) $selectedVenueId : null;
    }

    public function apply()
    {
        $user = Auth::user();

        if (! $user || ! $user->isEmployee() || ! $this->selectedVenueId) {
            return;
        }

        $isAssigned = $user->venues()
            ->active()
            ->whereKey($this->selectedVenueId)
            ->exists();

        if (! $isAssigned) {
            $this->addError('selectedVenueId', 'Select a valid assigned venue.');

            return;
        }

        session()->put('selected_venue_id', $this->selectedVenueId);
        session()->flash('status', 'Venue switched successfully.');

        return redirect(request()->header('Referer') ?: route('employee.dashboard'));
    }

    public function render()
    {
        return view('livewire.access.venue-switcher');
    }
}
