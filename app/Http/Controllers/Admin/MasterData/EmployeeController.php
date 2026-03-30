<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\StoreEmployeeRequest;
use App\Http\Requests\Admin\MasterData\UpdateEmployeeRequest;
use App\Models\User;
use App\Support\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $employee = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'is_active' => $request->boolean('is_active'),
            'password' => Hash::make($request->validated('password')),
        ]);

        return redirect()
            ->route('admin.master-data.employees.edit', $employee)
            ->with('status', 'User account created successfully.');
    }

    public function edit(User $employee): View
    {
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

        $employee->update($payload);

        if (! $employee->supportsFrozenFund()) {
            foreach ($employee->venues()->pluck('venues.id') as $venueId) {
                $employee->venues()->updateExistingPivot($venueId, ['frozen_fund_minor' => 0]);
            }
        }

        return redirect()
            ->route('admin.master-data.employees.edit', $employee)
            ->with('status', 'User account updated successfully.');
    }
}
