<?php

namespace App\Http\Middleware;

use App\Support\Role;
use Closure;
use Illuminate\Http\Request;

class EnsureVenueSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role === Role::ADMIN) {
            return $next($request);
        }

        $selectedVenueId = (int) $request->session()->get('selected_venue_id');

        if (! $selectedVenueId) {
            return redirect()
                ->route('venues.select')
                ->with('status', 'Select one of your assigned venues before entering the workspace.');
        }

        $hasVenue = $user->venues()
            ->active()
            ->whereKey($selectedVenueId)
            ->exists();

        if (! $hasVenue) {
            $request->session()->forget('selected_venue_id');

            return redirect()
                ->route('venues.select')
                ->withErrors(['venue_id' => 'Your last venue selection is no longer valid. Please choose another venue.']);
        }

        return $next($request);
    }
}
