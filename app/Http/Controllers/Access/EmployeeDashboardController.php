<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $selectedVenueId = (int) $request->session()->get('selected_venue_id');
        $venue = $user->venues()->whereKey($selectedVenueId)->firstOrFail();

        return view('access.employee.dashboard', [
            'venue' => $venue,
            'roleLabel' => $user->roleLabel(),
            'modules' => \App\Support\Role::modulesFor($user->role),
            'headline' => \App\Support\Role::dashboardHeadline($user->role),
        ]);
    }
}
