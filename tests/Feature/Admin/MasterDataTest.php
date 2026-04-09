<?php

namespace Tests\Feature\Admin;

use App\Models\Package;
use App\Models\PackageServiceAssignment;
use App\Models\PrintSetting;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_access_admin_master_data_routes(): void
    {
        $employee = User::factory()->employeeA()->create();

        $this->actingAs($employee)
            ->get(route('admin.master-data.venues.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_venue_with_four_vendor_slots(): void
    {
        $admin = User::factory()->admin()->create();
        $employeeA = User::factory()->employeeA()->create();
        $employeeB = User::factory()->employeeB()->create();

        $response = $this->actingAs($admin)->post(route('admin.master-data.venues.store'), [
            'name' => 'North Hall',
            'code' => 'NH-01',
            'is_active' => '1',
            'employee_ids' => [$employeeA->id, $employeeB->id],
            'vendor_slots' => [
                1 => 'Lights',
                2 => 'Sound',
                3 => 'Catering',
                4 => 'Florist',
            ],
        ]);

        $response->assertRedirect(route('admin.master-data.venues.index'));
        $this->assertDatabaseHas('venues', [
            'name' => 'North Hall',
            'code' => 'NH-01',
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('venue_vendors', 4);
        $this->assertDatabaseHas('venue_vendors', [
            'slot_number' => 1,
            'name' => 'Lights',
        ]);
        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employeeA->id,
            'venue_id' => Venue::firstOrFail()->id,
        ]);
        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employeeB->id,
            'venue_id' => Venue::firstOrFail()->id,
        ]);
    }

    public function test_admin_can_create_service_and_package_mapping(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceA = Service::factory()->create();
        $serviceB = Service::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.master-data.packages.store'), [
            'name' => 'Signature Package',
            'code' => 'PK-100',
            'description' => 'Premium layout',
            'is_active' => '1',
            'service_ids' => [$serviceA->id, $serviceB->id],
            'sort_orders' => [
                $serviceA->id => 2,
                $serviceB->id => 1,
            ],
        ]);

        $response->assertRedirect(route('admin.master-data.packages.index'));

        $package = Package::firstOrFail();

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'name' => 'Signature Package',
            'code' => 'PK-100',
        ]);
        $this->assertDatabaseHas('package_service', [
            'package_id' => $package->id,
            'service_id' => $serviceA->id,
            'sort_order' => 2,
        ]);
        $this->assertDatabaseHas('package_service', [
            'package_id' => $package->id,
            'service_id' => $serviceB->id,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_create_service_and_assign_it_to_packages_from_service_screen(): void
    {
        $admin = User::factory()->admin()->create();
        $packageA = Package::factory()->create();
        $packageB = Package::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.master-data.services.store'), [
            'name' => 'Ceiling Lights',
            'code' => 'SERV-CL',
            'standard_rate' => '275.00',
            'person_input_mode' => 'employee',
            'notes' => 'Lighting setup',
            'is_active' => '1',
            'package_ids' => [$packageA->id, $packageB->id],
        ]);

        $response->assertRedirect(route('admin.master-data.services.index'));

        $service = Service::query()->where('code', 'SERV-CL')->firstOrFail();

        $this->assertDatabaseHas('package_service', [
            'package_id' => $packageA->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('package_service', [
            'package_id' => $packageB->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'person_input_mode' => 'employee',
            'default_persons' => null,
        ]);
    }

    public function test_admin_can_create_type_a_employee_with_initial_venues_and_frozen_fund(): void
    {
        $admin = User::factory()->admin()->create();
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.master-data.employees.store'), [
            'name' => 'Employee A',
            'email' => 'employee.a@example.test',
            'role' => 'employee_a',
            'is_active' => '1',
            'venue_ids' => [$venueA->id, $venueB->id],
            'frozen_funds' => [
                $venueA->id => '1500.00',
                $venueB->id => '250.50',
            ],
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $employee = User::query()->where('email', 'employee.a@example.test')->firstOrFail();

        $response->assertRedirect(route('admin.master-data.employees.assignments.edit', $employee));

        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employee->id,
            'venue_id' => $venueA->id,
            'frozen_fund_minor' => 150000,
        ]);
        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employee->id,
            'venue_id' => $venueB->id,
            'frozen_fund_minor' => 25050,
        ]);
    }

    public function test_admin_can_update_employee_assignments_with_frozen_fund_for_type_a(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();
        $service = Service::factory()->create();
        $package = Package::factory()->create();
        $package->services()->attach($service->id, ['sort_order' => 1]);

        $response = $this->actingAs($admin)->put(route('admin.master-data.employees.assignments.update', $employee), [
            'venue_ids' => [$venueA->id, $venueB->id],
            'frozen_funds' => [
                $venueA->id => '1250.50',
                $venueB->id => '800.00',
            ],
            'service_ids_by_venue' => [
                $venueA->id => [$service->id],
                $venueB->id => [$service->id],
            ],
            'package_ids_by_venue' => [
                $venueA->id => [$package->id],
            ],
            'package_service_ids_by_venue' => [
                $venueA->id => [
                    $package->id => [$service->id],
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employee->id,
            'venue_id' => $venueA->id,
            'frozen_fund_minor' => 125050,
        ]);
        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employee->id,
            'venue_id' => $venueB->id,
            'frozen_fund_minor' => 80000,
        ]);
        $this->assertDatabaseHas('service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venueA->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('package_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venueA->id,
            'package_id' => $package->id,
        ]);
        $this->assertDatabaseHas('package_service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venueA->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_selected_packages_derive_service_access_in_employee_setup_workspace(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $serviceA = Service::factory()->create();
        $serviceB = Service::factory()->create();
        $extraService = Service::factory()->create();
        $package = Package::factory()->create();
        $package->services()->attach([
            $serviceA->id => ['sort_order' => 1],
            $serviceB->id => ['sort_order' => 2],
        ]);

        $response = $this->actingAs($admin)->put(route('admin.master-data.employees.assignments.update', $employee), [
            'venue_ids' => [$venue->id],
            'package_ids_by_venue' => [
                $venue->id => [$package->id],
            ],
            'package_service_ids_by_venue' => [
                $venue->id => [
                    $package->id => [$serviceA->id, $serviceB->id],
                ],
            ],
            'service_ids_by_venue' => [
                $venue->id => [$extraService->id],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('package_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $this->assertDatabaseHas('service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'service_id' => $serviceA->id,
        ]);
        $this->assertDatabaseHas('service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'service_id' => $serviceB->id,
        ]);
        $this->assertDatabaseHas('service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'service_id' => $extraService->id,
        ]);
        $this->assertDatabaseHas('package_service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $serviceA->id,
        ]);
    }

    public function test_same_service_can_be_assigned_in_multiple_packages_for_the_same_employee_and_venue(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create();
        $sharedService = Service::factory()->create();
        $packageA = Package::factory()->create();
        $packageB = Package::factory()->create();

        $packageA->services()->attach($sharedService->id, ['sort_order' => 1]);
        $packageB->services()->attach($sharedService->id, ['sort_order' => 1]);

        $response = $this->actingAs($admin)->put(route('admin.master-data.employees.assignments.update', $employee), [
            'venue_ids' => [$venue->id],
            'package_ids_by_venue' => [
                $venue->id => [$packageA->id, $packageB->id],
            ],
            'package_service_ids_by_venue' => [
                $venue->id => [
                    $packageA->id => [$sharedService->id],
                    $packageB->id => [$sharedService->id],
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('package_service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $packageA->id,
            'service_id' => $sharedService->id,
        ]);
        $this->assertDatabaseHas('package_service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $packageB->id,
            'service_id' => $sharedService->id,
        ]);
        $this->assertSame(1, PackageServiceAssignment::query()
            ->where('user_id', $employee->id)
            ->where('venue_id', $venue->id)
            ->where('service_id', $sharedService->id)
            ->distinct('service_id')
            ->count('service_id'));
        $this->assertDatabaseHas('service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'service_id' => $sharedService->id,
        ]);
    }

    public function test_non_type_a_employee_cannot_persist_frozen_fund_values(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.master-data.employees.assignments.update', $employee), [
            'venue_ids' => [$venue->id],
            'frozen_funds' => [
                $venue->id => '999.99',
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'frozen_fund_minor' => 0,
        ]);
    }

    public function test_admin_can_update_function_print_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('admin.master-data.function-print-settings.update'), [
            'function_terms_and_conditions' => "1. Terms updated.\n2. Print only after review.",
        ]);

        $response->assertRedirect(route('admin.master-data.function-print-settings.edit'));

        $this->assertSame(
            "1. Terms updated.\n2. Print only after review.",
            PrintSetting::current()->function_terms_and_conditions
        );
    }
}
