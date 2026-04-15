<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\StoreVenueRequest;
use App\Http\Requests\Admin\MasterData\UpdateVenueRequest;
use App\Models\PackageAssignment;
use App\Models\ServiceAssignment;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueVendor;
use App\Support\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        $query = Venue::query()->withCount(['users', 'vendors']);

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->value()) {
            $query->where('is_active', $status === 'active');
        }

        return view('admin.master-data.venues.index', [
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => Venue::count(),
                'active' => Venue::where('is_active', true)->count(),
                'assigned_employees' => DB::table('user_venue')->distinct()->count('user_id'),
                'vendor_slots' => VenueVendor::count(),
            ],
            'venues' => $query->orderBy('name')->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.master-data.venues.form', [
            'isEditing' => false,
            'venue' => new Venue(['is_active' => true]),
            'employees' => User::query()->whereIn('role', Role::employeeRoles())->orderBy('name')->get(),
            'selectedEmployeeIds' => [],
            'vendorSlots' => $this->normalizeVendorSlots(collect()),
        ]);
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        $venue = null;

        DB::transaction(function () use ($request, &$venue) {
            $venue = Venue::create([
                'name' => $request->validated('name'),
                'code' => $request->validated('code'),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncVendorSlots($venue, $request->validated('vendor_slots'));
            $this->syncEmployees($venue, $request->validated('employee_ids', []));
        });

        return redirect()
            ->route('admin.master-data.venues.index')
            ->with('status', 'Venue created successfully.');
    }

    public function edit(Venue $venue): View
    {
        $venue->loadCount('users');
        $venue->load('vendors');

        return view('admin.master-data.venues.form', [
            'isEditing' => true,
            'venue' => $venue,
            'employees' => User::query()->whereIn('role', Role::employeeRoles())->orderBy('name')->get(),
            'selectedEmployeeIds' => $venue->users()->pluck('users.id')->all(),
            'vendorSlots' => $this->normalizeVendorSlots($venue->vendors),
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        DB::transaction(function () use ($request, $venue) {
            $venue->update([
                'name' => $request->validated('name'),
                'code' => $request->validated('code'),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncVendorSlots($venue, $request->validated('vendor_slots'));
            $this->syncEmployees($venue, $request->validated('employee_ids', []));
        });

        return redirect()
            ->route('admin.master-data.venues.edit', $venue)
            ->with('status', 'Venue updated successfully.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        if (
            $venue->users()->exists()
            || $venue->functionEntries()->exists()
            || $venue->dailyIncomeEntries()->exists()
            || $venue->dailyBillingEntries()->exists()
            || $venue->vendorEntries()->exists()
        ) {
            return redirect()
                ->route('admin.master-data.venues.index')
                ->with('error', 'This venue is still assigned or has recorded activity. Remove the links or history dependency before deleting it.');
        }

        $venue->delete();

        return redirect()
            ->route('admin.master-data.venues.index')
            ->with('status', 'Venue deleted successfully.');
    }

    protected function syncVendorSlots(Venue $venue, array $vendorSlots): void
    {
        DB::transaction(function () use ($venue, $vendorSlots) {
            foreach (range(1, 4) as $slotNumber) {
                $name = trim((string) ($vendorSlots[$slotNumber] ?? ''));

                $venue->vendors()->updateOrCreate(
                    ['slot_number' => $slotNumber],
                    ['name' => $name !== '' ? $name : "Vendor {$slotNumber}"]
                );
            }
        });
    }

    protected function normalizeVendorSlots($vendors): array
    {
        $mapped = $vendors->keyBy('slot_number');

        return collect(range(1, 4))
            ->mapWithKeys(fn (int $slotNumber) => [
                $slotNumber => $mapped->get($slotNumber)?->name ?? "Vendor {$slotNumber}",
            ])
            ->all();
    }

    protected function syncEmployees(Venue $venue, array $employeeIds): void
    {
        $selectedEmployeeIds = User::query()
            ->whereIn('id', collect($employeeIds)->map(fn ($employeeId) => (int) $employeeId)->all())
            ->whereIn('role', Role::employeeRoles())
            ->pluck('id')
            ->all();

        $currentEmployeeIds = $venue->users()->pluck('users.id')->all();
        $removedEmployeeIds = array_values(array_diff($currentEmployeeIds, $selectedEmployeeIds));

        $venue->users()->sync($selectedEmployeeIds);

        if ($removedEmployeeIds !== []) {
            ServiceAssignment::query()->where('venue_id', $venue->id)->whereIn('user_id', $removedEmployeeIds)->delete();
            PackageAssignment::query()->where('venue_id', $venue->id)->whereIn('user_id', $removedEmployeeIds)->delete();
        }
    }
}
