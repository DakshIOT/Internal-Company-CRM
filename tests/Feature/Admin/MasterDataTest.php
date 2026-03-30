<?php

namespace Tests\Feature\Admin;

use App\Models\Package;
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

        $response = $this->actingAs($admin)->post(route('admin.master-data.venues.store'), [
            'name' => 'North Hall',
            'code' => 'NH-01',
            'is_active' => '1',
            'vendor_slots' => [
                1 => 'Lights',
                2 => 'Sound',
                3 => 'Catering',
                4 => 'Florist',
            ],
        ]);

        $response->assertRedirect();
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

        $response->assertRedirect();

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

    public function test_admin_can_update_employee_assignments_with_frozen_fund_for_type_a(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();
        $service = Service::factory()->create();
        $package = Package::factory()->create();

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
}
