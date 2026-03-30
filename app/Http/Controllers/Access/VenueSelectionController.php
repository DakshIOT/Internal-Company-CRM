<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VenueSelectionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $venues = $user->venues()->active()->orderBy('name')->get();

        return view('access.venues.select', [
            'venues' => $venues,
            'selectedVenueId' => (int) $request->session()->get('selected_venue_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->persistSelection($request, route('employee.dashboard'));
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->persistSelection($request, url()->previous() ?: route('employee.dashboard'));
    }

    protected function persistSelection(Request $request, string $fallbackRedirect): RedirectResponse
    {
        $validated = $request->validate([
            'venue_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $venue = $user->venues()
            ->active()
            ->whereKey($validated['venue_id'])
            ->first();

        if (! $venue instanceof Venue) {
            return back()->withErrors([
                'venue_id' => 'Select a valid assigned venue to continue.',
            ]);
        }

        $request->session()->put('selected_venue_id', $venue->getKey());

        return redirect()->to($fallbackRedirect)->with('status', 'Venue context updated successfully.');
    }
}
