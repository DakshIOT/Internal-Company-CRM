<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $selectedVenueId = (int) $request->session()->get('selected_venue_id');

        if (! $selectedVenueId) {
            return redirect()->route('venues.select');
        }

        $hasVenue = $user->venues()
            ->active()
            ->whereKey($selectedVenueId)
            ->exists();

        if (! $hasVenue) {
            $request->session()->forget('selected_venue_id');

            return redirect()->route('venues.select');
        }

        return redirect()->route('employee.dashboard');
    }
}
