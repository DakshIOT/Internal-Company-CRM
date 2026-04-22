<?php

namespace Tests\Feature\Admin;

use App\Models\FunctionEntry;
use App\Models\FunctionPackage;
use App\Models\FunctionServiceLine;
use App\Models\Package;
use App\Models\PackageServiceAssignment;
use App\Models\PrintSetting;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $package = Package::firstOrFail();

        $response->assertRedirect(route('admin.master-data.packages.edit', $package));


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

    public function test_admin_can_edit_service_and_save_updated_rate_notes_and_package_mapping(): void
    {
        $admin = User::factory()->admin()->create();
        $oldPackage = Package::factory()->create();
        $newPackage = Package::factory()->create();
        $service = Service::factory()->create([
            'name' => 'Original Service',
            'code' => 'SERV-OLD',
            'standard_rate_minor' => 15000,
            'person_input_mode' => Service::PERSON_MODE_FIXED,
            'default_persons' => 2,
            'notes' => 'Original notes',
            'is_active' => true,
        ]);
        $service->packages()->attach($oldPackage->id, ['sort_order' => 1]);

        $this->actingAs($admin)
            ->put(route('admin.master-data.services.update', $service), [
                'name' => 'Updated Service',
                'code' => 'SERV-NEW',
                'standard_rate' => '325.50',
                'person_input_mode' => Service::PERSON_MODE_EMPLOYEE,
                'default_persons' => '',
                'notes' => 'Updated service notes',
                'is_active' => '1',
                'package_ids' => [$newPackage->id],
            ])
            ->assertRedirect(route('admin.master-data.services.edit', $service));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Updated Service',
            'code' => 'SERV-NEW',
            'standard_rate_minor' => 32550,
            'person_input_mode' => Service::PERSON_MODE_EMPLOYEE,
            'default_persons' => null,
            'notes' => 'Updated service notes',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('package_service', [
            'package_id' => $oldPackage->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('package_service', [
            'package_id' => $newPackage->id,
            'service_id' => $service->id,
            'sort_order' => 1,
        ]);
    }

    public function test_service_edit_form_uses_plain_decimal_input_and_allows_resave_without_retyping_rate(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create([
            'name' => 'Large Rate Service',
            'standard_rate_minor' => 100000,
            'notes' => 'Old notes',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.services.edit', $service))
            ->assertOk()
            ->assertSee('value="1000.00"', false)
            ->assertDontSee('value="1,000.00"', false);

        $this->actingAs($admin)
            ->put(route('admin.master-data.services.update', $service), [
                'name' => $service->name,
                'code' => $service->code,
                'standard_rate' => '1000.00',
                'person_input_mode' => $service->person_input_mode,
                'default_persons' => $service->default_persons,
                'notes' => 'Updated notes only',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.master-data.services.edit', $service));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'standard_rate_minor' => 100000,
            'notes' => 'Updated notes only',
        ]);
    }

    public function test_admin_can_edit_package_and_save_updated_details_and_service_mapping(): void
    {
        $admin = User::factory()->admin()->create();
        $serviceA = Service::factory()->create();
        $serviceB = Service::factory()->create();
        $package = Package::factory()->create([
            'name' => 'Old Package',
            'code' => 'OLD-PKG',
            'description' => 'Old description',
            'is_active' => true,
        ]);
        $package->services()->attach($serviceA->id, ['sort_order' => 1]);

        $this->actingAs($admin)
            ->put(route('admin.master-data.packages.update', $package), [
                'name' => 'Updated Package',
                'code' => 'NEW-PKG',
                'description' => 'Updated description',
            ])
            ->assertRedirect(route('admin.master-data.packages.edit', $package));

        $this->actingAs($admin)
            ->put(route('admin.master-data.packages.mapping.update', $package), [
                'visible_service_ids' => [$serviceA->id, $serviceB->id],
                'service_ids' => [$serviceB->id],
                'sort_orders' => [$serviceB->id => 3],
            ])
            ->assertRedirect(route('admin.master-data.packages.edit', $package));

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'name' => 'Updated Package',
            'code' => 'NEW-PKG',
            'description' => 'Updated description',
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('package_service', [
            'package_id' => $package->id,
            'service_id' => $serviceA->id,
        ]);
        $this->assertDatabaseHas('package_service', [
            'package_id' => $package->id,
            'service_id' => $serviceB->id,
            'sort_order' => 3,
        ]);
    }

    public function test_package_edit_service_catalog_uses_search_and_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::factory()->create();

        Service::factory()->count(60)->create();
        $targetService = Service::factory()->create([
            'name' => 'Needle Service',
            'code' => 'NEEDLE',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.packages.edit', $package))
            ->assertOk()
            ->assertSee('Save visible page mapping')
            ->assertSee('Only 50 services load at a time.')
            ->assertSee('service_page=2', false);

        $this->actingAs($admin)
            ->get(route('admin.master-data.packages.edit', [
                'package' => $package,
                'service_search' => 'Needle',
            ]))
            ->assertOk()
            ->assertSee('Needle Service')
            ->assertSee('NEEDLE');
    }

    public function test_employee_assignment_modal_pagination_links_preserve_modal_state(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $selectedPackage = Package::factory()->create();

        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 0]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $selectedPackage->id,
        ]);

        Package::factory()->count(105)->create();
        Service::factory()->count(105)->create();

        $this->actingAs($admin)
            ->get(route('admin.master-data.employees.assignments.edit', [
                'employee' => $employee,
                'venue' => $venue->id,
                'package' => $selectedPackage->id,
                'open_modal' => 'assign-package-modal',
            ]))
            ->assertOk()
            ->assertSee('Only 100 packages load at a time.')
            ->assertSee('open_modal=assign-package-modal', false)
            ->assertSee('available_package_page=2', false);

        $this->actingAs($admin)
            ->get(route('admin.master-data.employees.assignments.edit', [
                'employee' => $employee,
                'venue' => $venue->id,
                'package' => $selectedPackage->id,
                'open_modal' => 'assign-service-modal',
            ]))
            ->assertOk()
            ->assertSee('Only 100 services load at a time.')
            ->assertSee('open_modal=assign-service-modal', false)
            ->assertSee('available_service_page=2', false);
    }

    public function test_admin_can_edit_venue_and_save_updated_vendor_slots_and_employee_links(): void
    {
        $admin = User::factory()->admin()->create();
        $employeeA = User::factory()->employeeA()->create();
        $employeeB = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create([
            'name' => 'Old Venue',
            'code' => 'OLD-VENUE',
            'is_active' => true,
        ]);
        $venue->vendors()->createMany([
            ['slot_number' => 1, 'name' => 'Old Vendor 1'],
            ['slot_number' => 2, 'name' => 'Old Vendor 2'],
            ['slot_number' => 3, 'name' => 'Old Vendor 3'],
            ['slot_number' => 4, 'name' => 'Old Vendor 4'],
        ]);
        $venue->users()->attach($employeeA->id, ['frozen_fund_minor' => 0]);

        $this->actingAs($admin)
            ->put(route('admin.master-data.venues.update', $venue), [
                'name' => 'Updated Venue',
                'code' => 'NEW-VENUE',
                'vendor_slots' => [
                    1 => 'Lighting',
                    2 => 'Sound',
                    3 => 'Catering',
                    4 => 'Florist',
                ],
                'employee_ids' => [$employeeB->id],
            ])
            ->assertRedirect(route('admin.master-data.venues.edit', $venue));

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Updated Venue',
            'code' => 'NEW-VENUE',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('venue_vendors', [
            'venue_id' => $venue->id,
            'slot_number' => 1,
            'name' => 'Lighting',
        ]);
        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employeeB->id,
            'venue_id' => $venue->id,
        ]);
        $this->assertDatabaseMissing('user_venue', [
            'user_id' => $employeeA->id,
            'venue_id' => $venue->id,
        ]);
    }

    public function test_admin_can_add_multiple_service_attachments_and_remove_one_from_edit_page(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();
        $existingAttachment = $service->attachments()->create([
            'uploaded_by' => $admin->id,
            'disk' => 'local',
            'storage_path' => 'attachments/service/existing-guide.pdf',
            'original_name' => 'existing-guide.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.services.edit', $service))
            ->assertOk()
            ->assertSee('form="attachment-delete-'.$existingAttachment->id.'"', false)
            ->assertSee('id="attachment-delete-'.$existingAttachment->id.'"', false);

        $this->actingAs($admin)
            ->put(route('admin.master-data.services.update', $service), [
                'name' => $service->name,
                'code' => $service->code,
                'standard_rate' => '150.00',
                'person_input_mode' => Service::PERSON_MODE_EMPLOYEE,
                'notes' => 'Updated notes',
                'is_active' => '1',
                'attachments' => [
                    UploadedFile::fake()->create('setup-guide.pdf', 40, 'application/pdf'),
                    UploadedFile::fake()->image('reference-photo.jpg'),
                ],
            ])
            ->assertRedirect(route('admin.master-data.services.edit', $service));

        $this->assertSame(3, $service->fresh()->attachments()->count());
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Service::class,
            'attachable_id' => $service->id,
            'original_name' => 'setup-guide.pdf',
        ]);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Service::class,
            'attachable_id' => $service->id,
            'original_name' => 'reference-photo.jpg',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.master-data.services.attachments.destroy', [
                'service' => $service,
                'attachment' => $existingAttachment,
            ]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Service attachment removed.');

        $this->assertDatabaseMissing('attachments', [
            'id' => $existingAttachment->id,
        ]);
        $this->assertSame(2, $service->fresh()->attachments()->count());
    }

    public function test_admin_can_create_type_a_employee_and_continue_to_setup_workspace(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.master-data.employees.store'), [
            'name' => 'Employee A',
            'email' => 'employee.a@example.test',
            'role' => 'employee_a',
            'is_active' => '1',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $employee = User::query()->where('email', 'employee.a@example.test')->firstOrFail();

        $response->assertRedirect(route('admin.master-data.employees.assignments.edit', $employee));
        $this->assertDatabaseCount('user_venue', 0);
    }

    public function test_admin_can_build_employee_setup_workspace_with_new_venue_package_and_service(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();

        $this->actingAs($admin)->post(route('admin.master-data.employees.assignments.venues.store', $employee), [
            'name' => 'Garden Court',
            'code' => 'GC-01',
            'vendor_slots' => [
                1 => 'Vendor 1',
                2 => 'Vendor 2',
                3 => 'Vendor 3',
                4 => 'Vendor 4',
            ],
            'frozen_fund' => '450.00',
        ])->assertRedirect();

        $venue = Venue::query()->where('code', 'GC-01')->firstOrFail();

        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'frozen_fund_minor' => 45000,
        ]);

        $this->actingAs($admin)->post(route('admin.master-data.employees.assignments.packages.store', [$employee, $venue]), [
            'name' => 'Corporate Lite',
            'code' => 'CORP-LITE',
            'description' => 'New venue package',
        ])->assertRedirect();

        $package = Package::query()->where('code', 'CORP-LITE')->firstOrFail();

        $this->assertDatabaseHas('package_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);

        $this->actingAs($admin)->post(route('admin.master-data.employees.assignments.services.store', [$employee, $venue, $package]), [
            'name' => 'Lighting Rig',
            'code' => 'LIGHT-RIG',
            'standard_rate' => '125.00',
            'person_input_mode' => 'employee',
            'notes' => 'Adjustable staff count',
            'attachments' => [
                UploadedFile::fake()->create('service-guide.pdf', 40, 'application/pdf'),
            ],
        ])->assertRedirect();

        $service = Service::query()->where('code', 'LIGHT-RIG')->firstOrFail();

        $this->assertDatabaseHas('package_service', [
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('package_service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Service::class,
            'attachable_id' => $service->id,
            'original_name' => 'service-guide.pdf',
        ]);
    }

    public function test_admin_workspace_rejects_unsupported_service_attachment_types(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $package = Package::factory()->create();

        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 0]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.master-data.employees.assignments.edit', [
                'employee' => $employee,
                'venue' => $venue->id,
                'package' => $package->id,
            ]))
            ->post(route('admin.master-data.employees.assignments.services.store', [$employee, $venue, $package]), [
                'name' => 'Invalid Attachment Service',
                'standard_rate' => '50.00',
                'person_input_mode' => 'none',
                'attachments' => [
                    UploadedFile::fake()->create('rules.txt', 4, 'text/plain'),
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('services', [
            'name' => 'Invalid Attachment Service',
        ]);
    }

    public function test_admin_can_attach_existing_venue_package_and_service_inside_employee_setup_workspace(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create();
        $package = Package::factory()->create();
        $service = Service::factory()->create();
        $package->services()->attach($service->id, ['sort_order' => 1]);

        $this->actingAs($admin)->post(route('admin.master-data.employees.assignments.venues.attach', $employee), [
            'venue_id' => $venue->id,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.master-data.employees.assignments.packages.attach', [$employee, $venue]), [
            'package_id' => $package->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
        ]);
        $this->assertDatabaseHas('package_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $this->assertDatabaseHas('package_service', [
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('package_service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.master-data.employees.assignments.services.bulk-destroy', [$employee, $venue, $package]), [
            'service_ids' => [$service->id],
        ])->assertRedirect();

        $this->assertDatabaseMissing('package_service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseMissing('service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'service_id' => $service->id,
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

    public function test_admin_can_render_employee_setup_workspace_for_selected_venue_and_package(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create(['name' => 'Garden Court']);
        $package = Package::factory()->create(['name' => 'Corporate Lite']);
        $service = Service::factory()->create(['name' => 'Lighting Rig']);

        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 45000]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.employees.assignments.edit', [
                'employee' => $employee,
                'venue' => $venue->id,
                'package' => $package->id,
            ]))
            ->assertOk()
            ->assertSee('Employee setup workspace')
            ->assertSee('Garden Court')
            ->assertSee('Corporate Lite')
            ->assertSee('Lighting Rig')
            ->assertSee('Assign existing venue')
            ->assertSee('Assign existing package')
            ->assertSee('Assign existing service');
    }

    public function test_inactive_assigned_package_is_marked_and_warned_in_employee_setup_workspace(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create(['name' => 'Garden Court']);
        $package = Package::factory()->create([
            'name' => 'Legacy Package',
            'is_active' => false,
        ]);

        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 0]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.employees.assignments.edit', [
                'employee' => $employee,
                'venue' => $venue->id,
                'package' => $package->id,
            ]))
            ->assertOk()
            ->assertSee('Inactive package')
            ->assertSee('It remains visible only because it is still assigned to this employee venue.')
            ->assertSee('They will not appear in active package assignment lists or employee package selection.');
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

    public function test_admin_can_delete_employee_without_deleting_master_data_records(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create();
        $package = Package::factory()->create();
        $service = Service::factory()->create();

        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 0]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.master-data.employees.destroy', $employee))
            ->assertRedirect(route('admin.master-data.employees.index'));

        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseMissing('user_venue', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
        ]);
        $this->assertDatabaseMissing('package_assignments', [
            'user_id' => $employee->id,
            'package_id' => $package->id,
        ]);
        $this->assertDatabaseMissing('package_service_assignments', [
            'user_id' => $employee->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseMissing('service_assignments', [
            'user_id' => $employee->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('venues', ['id' => $venue->id]);
        $this->assertDatabaseHas('packages', ['id' => $package->id]);
        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_admin_can_deactivate_package_and_it_disappears_from_employee_setup_assignment_lists(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $package = Package::factory()->create([
            'name' => 'Deactivate Me Package',
            'is_active' => true,
        ]);

        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 0]);

        $this->actingAs($admin)
            ->patch(route('admin.master-data.packages.toggle-active', $package))
            ->assertRedirect(route('admin.master-data.packages.index'))
            ->assertSessionHas('status', 'Package deactivated successfully.');

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.employees.assignments.edit', [
                'employee' => $employee,
                'venue' => $venue->id,
            ]))
            ->assertOk()
            ->assertDontSee('Deactivate Me Package');
    }

    public function test_admin_can_deactivate_service_and_it_disappears_from_employee_setup_assignment_lists(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $package = Package::factory()->create();
        $service = Service::factory()->create([
            'name' => 'Deactivate Me Service',
            'is_active' => true,
        ]);

        $package->services()->attach($service->id, ['sort_order' => 1]);
        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 0]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.master-data.services.toggle-active', $service))
            ->assertRedirect(route('admin.master-data.services.index'))
            ->assertSessionHas('status', 'Service deactivated successfully.');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.employees.assignments.edit', [
                'employee' => $employee,
                'venue' => $venue->id,
                'package' => $package->id,
            ]))
            ->assertOk()
            ->assertDontSee('Deactivate Me Service');
    }

    public function test_package_index_uses_activate_and_deactivate_actions_instead_of_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $activePackage = Package::factory()->create([
            'name' => 'Active Package',
            'is_active' => true,
        ]);
        $inactivePackage = Package::factory()->create([
            'name' => 'Inactive Package',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.packages.index'))
            ->assertOk()
            ->assertSee('Active Package')
            ->assertSee('Inactive Package')
            ->assertSee('Deactivate')
            ->assertSee('Activate')
            ->assertDontSee('Delete this package?', false)
            ->assertDontSee('>Delete<', false);
    }

    public function test_service_index_uses_activate_and_deactivate_actions_instead_of_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $activeService = Service::factory()->create([
            'name' => 'Active Service',
            'is_active' => true,
        ]);
        $inactiveService = Service::factory()->create([
            'name' => 'Inactive Service',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.services.index'))
            ->assertOk()
            ->assertSee('Active Service')
            ->assertSee('Inactive Service')
            ->assertSee('Deactivate')
            ->assertSee('Activate')
            ->assertDontSee('Delete this service?', false)
            ->assertDontSee('>Delete<', false);
    }

    public function test_admin_cannot_delete_package_that_is_already_used_in_function_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $package = Package::factory()->create();
        $functionEntry = FunctionEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
        ]);

        FunctionPackage::query()->create([
            'function_entry_id' => $functionEntry->id,
            'package_id' => $package->id,
            'name_snapshot' => $package->name,
            'code_snapshot' => $package->code,
            'total_minor' => 100000,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.master-data.packages.destroy', $package))
            ->assertRedirect(route('admin.master-data.packages.index'))
            ->assertSessionHas('error', 'This package is already used in Function Entry records and cannot be deleted.');

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_admin_cannot_delete_service_that_is_already_used_in_function_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $package = Package::factory()->create();
        $service = Service::factory()->create();
        $functionEntry = FunctionEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
        ]);

        $functionPackage = FunctionPackage::query()->create([
            'function_entry_id' => $functionEntry->id,
            'package_id' => $package->id,
            'name_snapshot' => $package->name,
            'code_snapshot' => $package->code,
            'total_minor' => 100000,
        ]);

        FunctionServiceLine::query()->create([
            'function_package_id' => $functionPackage->id,
            'service_id' => $service->id,
            'sort_order' => 1,
            'is_selected' => true,
            'item_name_snapshot' => $service->name,
            'rate_minor' => 50000,
            'uses_persons' => false,
            'person_input_mode' => FunctionServiceLine::PERSON_MODE_NONE,
            'persons' => 0,
            'extra_charge_minor' => 0,
            'notes' => null,
            'line_total_minor' => 50000,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.master-data.services.destroy', $service))
            ->assertRedirect(route('admin.master-data.services.index'))
            ->assertSessionHas('error', 'This service is already used in Function Entry records and cannot be deleted.');

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_inactive_assigned_service_is_marked_and_warned_in_employee_setup_workspace(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create(['name' => 'Garden Court']);
        $package = Package::factory()->create(['name' => 'Corporate Lite']);
        $service = Service::factory()->create([
            'name' => 'Legacy Service',
            'is_active' => false,
        ]);

        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 0]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.employees.assignments.edit', [
                'employee' => $employee,
                'venue' => $venue->id,
                'package' => $package->id,
            ]))
            ->assertOk()
            ->assertSee('Inactive service')
            ->assertSee('They will not appear in active service assignment lists or future employee package rows.');
    }

    public function test_admin_cannot_delete_venue_that_has_recorded_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();

        FunctionEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.master-data.venues.destroy', $venue))
            ->assertRedirect(route('admin.master-data.venues.index'))
            ->assertSessionHas('error', 'This venue is still assigned or has recorded activity. Remove the links or history dependency before deleting it.');

        $this->assertDatabaseHas('venues', ['id' => $venue->id]);
    }

    public function test_editing_employee_account_does_not_remove_existing_setup_when_venue_fields_are_absent(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $package = Package::factory()->create();
        $service = Service::factory()->create();

        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 0]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($admin)->put(route('admin.master-data.employees.update', $employee), [
            'name' => 'Employee C Updated',
            'email' => $employee->email,
            'role' => 'employee_c',
            'is_active' => '1',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.master-data.employees.edit', $employee));

        $this->assertDatabaseHas('user_venue', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
        ]);
        $this->assertDatabaseHas('package_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $this->assertDatabaseHas('package_service_assignments', [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);
    }
}
