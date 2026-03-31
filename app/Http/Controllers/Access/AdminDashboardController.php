<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AdminIncomeEntry;
use App\Models\Package;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('access.admin.dashboard', [
            'cards' => [
                ['label' => 'Venues', 'value' => Venue::count(), 'hint' => 'Venue master records and vendor slots.'],
                ['label' => 'Employees', 'value' => User::where('role', '!=', 'admin')->count(), 'hint' => 'Internal employee accounts under admin control.'],
                ['label' => 'Services', 'value' => Service::count(), 'hint' => 'Service masters ready for package mapping and assignment.'],
                ['label' => 'Packages', 'value' => Package::count(), 'hint' => 'Package masters with ordered service composition.'],
                ['label' => 'Admin Income Entries', 'value' => AdminIncomeEntry::count(), 'hint' => 'Admin-only ledger entries with secure attachments.'],
            ],
            'quickLinks' => [
                ['label' => 'Admin income', 'route' => 'admin.admin-income.index'],
                ['label' => 'Manage venues', 'route' => 'admin.master-data.venues.index'],
                ['label' => 'Manage employees', 'route' => 'admin.master-data.employees.index'],
                ['label' => 'Manage services', 'route' => 'admin.master-data.services.index'],
                ['label' => 'Manage packages', 'route' => 'admin.master-data.packages.index'],
            ],
        ]);
    }
}
