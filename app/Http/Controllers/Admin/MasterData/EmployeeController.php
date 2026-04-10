<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\StoreEmployeeRequest;
use App\Http\Requests\Admin\MasterData\UpdateEmployeeRequest;
use App\Models\PackageAssignment;
use App\Models\PackageServiceAssignment;
use App\Models\ServiceAssignment;
use App\Models\User;
use App\Models\Venue;
use App\Support\Money;
use App\Support\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->withCount('venues');

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->string('role')->value()) {
            $query->where('role', $role);
        }

        if ($status = $request->string('status')->value()) {
            $query->where('is_active', $status === 'active');
        }

        return view('admin.master-data.employees.index', [
            'employees' => $query->orderBy('name')->paginate(12)->withQueryString(),
            'filters' => $request->only(['search', 'role', 'status']),
            'roleOptions' => Role::options(),
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'admins' => User::where('role', Role::ADMIN)->count(),
                'employees' => User::whereIn('role', Role::employeeRoles())->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.master-data.employees.form', [
            'employee' => new User(['is_active' => true, 'role' => Role::EMPLOYEE_C]),
            'isEditing' => false,
            'roleOptions' => Role::options(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = DB::transaction(function () use ($request) {
            $employee = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'role' => $request->validated('role'),
                'is_active' => $request->boolean('is_active'),
                'password' => Hash::make($request->validated('password')),
            ]);

            if ($request->has('venue_ids')) {
                $this->syncVenues($employee, $request->validated('venue_ids', []), $request->validated('frozen_funds', []));
            }

            return $employee;
        });

        return redirect()
            ->route($employee->isEmployee() ? 'admin.master-data.employees.assignments.edit' : 'admin.master-data.employees.edit', $employee)
            ->with('status', $employee->isEmployee()
                ? 'User account created. Finish package and service access in the employee setup workspace.'
                : 'User account created successfully.');
    }

    public function edit(User $employee): View
    {
        $employee->load('venues');
        $employee->loadCount('venues');

        return view('admin.master-data.employees.form', [
            'employee' => $employee,
            'isEditing' => true,
            'roleOptions' => Role::options(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        $payload = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->validated('password'));
        }

        DB::transaction(function () use ($employee, $payload, $request) {
            $employee->update($payload);

            if ($employee->isAdmin()) {
                $this->syncVenues($employee, [], []);

                return;
            }

            if ($request->has('venue_ids')) {
                $this->syncVenues($employee, $request->validated('venue_ids', []), $request->validated('frozen_funds', []));

                return;
            }

            if (! $employee->supportsFrozenFund()) {
                foreach ($employee->venues as $venue) {
                    $employee->venues()->updateExistingPivot($venue->id, ['frozen_fund_minor' => 0]);
                }
            }
        });

        return redirect()
            ->route('admin.master-data.employees.edit', $employee)
            ->with('status', 'User account updated successfully.');
    }

    private function syncVenues(User $employee, array $venueIds, array $frozenFunds): void
    {
        if ($employee->isAdmin()) {
            $employee->venues()->detach();
            ServiceAssignment::query()->where('user_id', $employee->id)->delete();
            PackageAssignment::query()->where('user_id', $employee->id)->delete();
            PackageServiceAssignment::query()->where('user_id', $employee->id)->delete();

            return;
        }

        $selectedVenueIds = collect($venueIds)->map(fn ($venueId) => (int) $venueId)->unique()->values();

        $venueSyncData = $selectedVenueIds->mapWithKeys(function (int $venueId) use ($employee, $frozenFunds) {
            return [$venueId => [
                'frozen_fund_minor' => $employee->supportsFrozenFund()
                    ? Money::toMinor($frozenFunds[$venueId] ?? null)
                    : 0,
            ]];
        })->all();

        $currentVenueIds = $employee->venues()->pluck('venues.id')->all();
        $removedVenueIds = array_values(array_diff($currentVenueIds, $selectedVenueIds->all()));

        $employee->venues()->sync($venueSyncData);

        if ($removedVenueIds !== []) {
            ServiceAssignment::query()->where('user_id', $employee->id)->whereIn('venue_id', $removedVenueIds)->delete();
            PackageAssignment::query()->where('user_id', $employee->id)->whereIn('venue_id', $removedVenueIds)->delete();
            PackageServiceAssignment::query()->where('user_id', $employee->id)->whereIn('venue_id', $removedVenueIds)->delete();
        }

        if (! $employee->supportsFrozenFund()) {
            foreach ($selectedVenueIds as $venueId) {
                $employee->venues()->updateExistingPivot($venueId, ['frozen_fund_minor' => 0]);
            }
        }
    }
}
