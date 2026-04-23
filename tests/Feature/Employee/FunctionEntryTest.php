<?php

namespace Tests\Feature\Employee;

use App\Exports\Reports\WorkbookExport;
use App\Models\Attachment;
use App\Models\FunctionEntry;
use App\Models\FunctionExtraCharge;
use App\Models\Package;
use App\Models\PackageServiceAssignment;
use App\Models\PrintSetting;
use App\Models\Service;
use App\Models\ServiceAssignment;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class FunctionEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_function_entry_with_base_attachments(): void
    {
        Storage::fake('local');

        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $response = $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-03-30',
                'name' => 'Spring Wedding',
                'notes' => 'Initial entry',
                'attachments' => [
                    UploadedFile::fake()->create('brief.png', 64, 'image/png'),
                ],
            ]);

        $functionEntry = FunctionEntry::firstOrFail();

        $response->assertRedirect(route('employee.functions.index'));

        $this->assertDatabaseHas('function_entries', [
            'id' => $functionEntry->id,
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'name' => 'Spring Wedding',
        ]);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => FunctionEntry::class,
            'attachable_id' => $functionEntry->id,
        ]);
    }

    public function test_employee_cannot_access_another_employees_function_entry(): void
    {
        $venue = Venue::factory()->create();
        $employee = User::factory()->employeeA()->create();
        $otherEmployee = User::factory()->employeeB()->create();
        $this->assignEmployeeToVenue($employee, $venue);
        $this->assignEmployeeToVenue($otherEmployee, $venue);

        $functionEntry = FunctionEntry::query()->create([
            'user_id' => $otherEmployee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-30',
            'name' => 'Private Booking',
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.edit', $functionEntry))
            ->assertForbidden();
    }

    public function test_function_entry_totals_recalculate_with_packages_charges_installments_discounts_and_frozen_fund(): void
    {
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue, 15000);

        $serviceA = Service::factory()->create([
            'standard_rate_minor' => 10000,
            'uses_persons' => true,
            'person_input_mode' => 'fixed',
            'default_persons' => 2,
        ]);
        $serviceB = Service::factory()->create([
            'standard_rate_minor' => 5000,
            'uses_persons' => true,
            'person_input_mode' => 'fixed',
            'default_persons' => 1,
        ]);
        $package = Package::factory()->create();
        $package->services()->attach([
            $serviceA->id => ['sort_order' => 1],
            $serviceB->id => ['sort_order' => 2],
        ]);

        $employee->serviceAssignments()->createMany([
            ['venue_id' => $venue->id, 'service_id' => $serviceA->id],
            ['venue_id' => $venue->id, 'service_id' => $serviceB->id],
        ]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->createMany([
            ['venue_id' => $venue->id, 'package_id' => $package->id, 'service_id' => $serviceA->id],
            ['venue_id' => $venue->id, 'package_id' => $package->id, 'service_id' => $serviceB->id],
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-03-30',
                'name' => 'Function Total Test',
                'notes' => 'Calculation flow',
            ])->assertRedirect();

        $functionEntry = FunctionEntry::firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])->assertRedirect();

        $functionPackage = $functionEntry->fresh()->packages()->with('serviceLines')->firstOrFail();
        $lineA = $functionPackage->serviceLines->firstWhere('service_id', $serviceA->id);
        $lineB = $functionPackage->serviceLines->firstWhere('service_id', $serviceB->id);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.functions.packages.update', [$functionEntry, $functionPackage]), [
                'service_lines' => [
                    $lineA->id => [
                        'is_selected' => '1',
                        'rate' => '100.00',
                        'extra_charge' => '25.00',
                        'notes' => 'Primary service',
                    ],
                    $lineB->id => [
                        'is_selected' => '1',
                        'rate' => '50.00',
                        'extra_charge' => '0.00',
                        'notes' => 'Secondary service',
                    ],
                ],
            ])->assertRedirect();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.extra-charges.store', $functionEntry), [
                'entry_date' => '2026-03-30',
                'name' => 'Flowers',
                'mode' => 'cash',
                'amount' => '30.00',
                'note' => 'Added decor',
            ])->assertRedirect();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.discounts.store', $functionEntry), [
                'entry_date' => '2026-03-30',
                'name' => 'Promo',
                'mode' => 'bank',
                'amount' => '50.00',
                'note' => 'Manual reduction',
            ])->assertRedirect();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.installments.store', $functionEntry), [
                'entry_date' => '2026-03-30',
                'name' => 'Advance',
                'mode' => 'upi',
                'amount' => '100.00',
                'note' => 'Advance payment',
            ])->assertRedirect();

        $functionEntry->refresh();
        $functionPackage->refresh();

        $this->assertSame(27500, (int) $functionPackage->total_minor);
        $this->assertSame(27500, (int) $functionEntry->package_total_minor);
        $this->assertSame(3000, (int) $functionEntry->extra_charge_total_minor);
        $this->assertSame(5000, (int) $functionEntry->discount_total_minor);
        $this->assertSame(25500, (int) $functionEntry->function_total_minor);
        $this->assertSame(10000, (int) $functionEntry->paid_total_minor);
        $this->assertSame(15500, (int) $functionEntry->pending_total_minor);
        $this->assertSame(15000, (int) $functionEntry->frozen_fund_minor);
        $this->assertSame(10500, (int) $functionEntry->net_total_after_frozen_fund_minor);
    }

    public function test_employee_can_enter_persons_for_employee_select_service_mode(): void
    {
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $service = Service::factory()->create([
            'standard_rate_minor' => 9000,
            'uses_persons' => true,
            'person_input_mode' => 'employee',
            'default_persons' => null,
        ]);
        $package = Package::factory()->create();
        $package->services()->attach($service->id, ['sort_order' => 1]);

        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-03-31',
                'name' => 'Editable Persons Test',
            ])->assertRedirect();

        $functionEntry = FunctionEntry::firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])->assertRedirect();

        $functionPackage = $functionEntry->fresh()->packages()->with('serviceLines')->firstOrFail();
        $line = $functionPackage->serviceLines->firstOrFail();

        $this->assertSame('employee', $line->person_input_mode);
        $this->assertSame(1, (int) $line->persons);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.functions.packages.update', [$functionEntry, $functionPackage]), [
                'service_lines' => [
                    $line->id => [
                        'is_selected' => '1',
                        'persons' => '3',
                        'rate' => '90.00',
                        'extra_charge' => '15.00',
                        'notes' => 'Employee entered persons',
                    ],
                ],
            ])->assertRedirect();

        $line->refresh();
        $functionEntry->refresh();

        $this->assertSame(3, (int) $line->persons);
        $this->assertSame(28500, (int) $line->line_total_minor);
        $this->assertSame(28500, (int) $functionEntry->package_total_minor);
    }

    public function test_flat_rate_service_mode_ignores_persons_and_uses_single_rate(): void
    {
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $service = Service::factory()->create([
            'standard_rate_minor' => 7000,
            'uses_persons' => false,
            'person_input_mode' => 'none',
            'default_persons' => null,
        ]);
        $package = Package::factory()->create();
        $package->services()->attach($service->id, ['sort_order' => 1]);

        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-04-01',
                'name' => 'Flat Rate Test',
            ])->assertRedirect();

        $functionEntry = FunctionEntry::firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])->assertRedirect();

        $functionPackage = $functionEntry->fresh()->packages()->with('serviceLines')->firstOrFail();
        $line = $functionPackage->serviceLines->firstOrFail();

        $this->assertSame('none', $line->person_input_mode);
        $this->assertSame(0, (int) $line->persons);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.functions.packages.update', [$functionEntry, $functionPackage]), [
                'service_lines' => [
                    $line->id => [
                        'is_selected' => '1',
                        'persons' => '9',
                        'rate' => '70.00',
                        'extra_charge' => '20.00',
                        'notes' => 'Flat rate row',
                    ],
                ],
            ])->assertRedirect();

        $line->refresh();
        $functionEntry->refresh();

        $this->assertSame(0, (int) $line->persons);
        $this->assertSame(9000, (int) $line->line_total_minor);
        $this->assertSame(9000, (int) $functionEntry->package_total_minor);
    }

    public function test_employee_package_rows_hide_service_rate_and_row_total_but_still_calculate_totals(): void
    {
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $service = Service::factory()->create([
            'name' => 'Private Rate Service',
            'standard_rate_minor' => 12000,
            'uses_persons' => true,
            'person_input_mode' => 'employee',
            'default_persons' => null,
        ]);
        $package = Package::factory()->create(['name' => 'Private Rate Package']);
        $package->services()->attach($service->id, ['sort_order' => 1]);

        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-04-23',
                'name' => 'Private Rate Function',
            ])->assertRedirect();

        $functionEntry = FunctionEntry::firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])->assertRedirect();

        $functionPackage = $functionEntry->fresh()->packages()->with('serviceLines')->firstOrFail();
        $line = $functionPackage->serviceLines->firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.edit', $functionEntry))
            ->assertOk()
            ->assertSee('Private Rate Service')
            ->assertSee('Package Total')
            ->assertDontSee('<th>Rate</th>', false)
            ->assertDontSee('<th>Row Total</th>', false)
            ->assertDontSee('name="service_lines['.$line->id.'][rate]"', false);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.functions.packages.update', [$functionEntry, $functionPackage]), [
                'service_lines' => [
                    $line->id => [
                        'is_selected' => '1',
                        'persons' => '2',
                        'extra_charge' => '30.00',
                        'notes' => 'Employee entered details without seeing rate',
                    ],
                ],
            ])->assertRedirect();

        $line->refresh();
        $functionEntry->refresh();
        $functionPackage->refresh();

        $this->assertSame(12000, (int) $line->rate_minor);
        $this->assertSame(27000, (int) $line->line_total_minor);
        $this->assertSame(27000, (int) $functionPackage->total_minor);
        $this->assertSame(27000, (int) $functionEntry->package_total_minor);
    }

    public function test_package_add_uses_package_specific_service_assignments_when_present(): void
    {
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue, 10000);

        $serviceA = Service::factory()->create(['name' => 'Photography']);
        $serviceB = Service::factory()->create(['name' => 'Decor']);
        $package = Package::factory()->create();
        $package->services()->attach([
            $serviceA->id => ['sort_order' => 1],
            $serviceB->id => ['sort_order' => 2],
        ]);

        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);

        ServiceAssignment::query()->insert([
            [
                'user_id' => $employee->id,
                'venue_id' => $venue->id,
                'service_id' => $serviceA->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $employee->id,
                'venue_id' => $venue->id,
                'service_id' => $serviceB->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        PackageServiceAssignment::query()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $serviceA->id,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-03-30',
                'name' => 'Scoped Package Service Test',
            ])->assertRedirect();

        $functionEntry = FunctionEntry::firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])->assertRedirect();

        $functionPackage = $functionEntry->fresh()->packages()->with('serviceLines')->firstOrFail();

        $this->assertCount(1, $functionPackage->serviceLines);
        $this->assertSame($serviceA->id, (int) $functionPackage->serviceLines->first()->service_id);
    }

    public function test_deactivated_services_do_not_show_up_in_new_or_synced_function_package_rows(): void
    {
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $activeService = Service::factory()->create([
            'name' => 'Active Photo',
            'is_active' => true,
        ]);
        $inactiveService = Service::factory()->create([
            'name' => 'Inactive Drone',
            'is_active' => false,
        ]);
        $package = Package::factory()->create(['name' => 'Filtered Package']);
        $package->services()->attach([
            $activeService->id => ['sort_order' => 1],
            $inactiveService->id => ['sort_order' => 2],
        ]);

        $employee->serviceAssignments()->createMany([
            ['venue_id' => $venue->id, 'service_id' => $activeService->id],
            ['venue_id' => $venue->id, 'service_id' => $inactiveService->id],
        ]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->createMany([
            ['venue_id' => $venue->id, 'package_id' => $package->id, 'service_id' => $activeService->id],
            ['venue_id' => $venue->id, 'package_id' => $package->id, 'service_id' => $inactiveService->id],
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-04-15',
                'name' => 'Inactive Service Filter',
            ])->assertRedirect();

        $functionEntry = FunctionEntry::firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])->assertRedirect();

        $functionPackage = $functionEntry->fresh()->packages()->with('serviceLines')->firstOrFail();
        $this->assertCount(1, $functionPackage->serviceLines);
        $this->assertNotNull($functionPackage->serviceLines->firstWhere('service_id', $activeService->id));
        $this->assertNull($functionPackage->serviceLines->firstWhere('service_id', $inactiveService->id));

        $reactivatedService = Service::factory()->create([
            'name' => 'Later Active Add',
            'is_active' => true,
        ]);
        $deactivatedLaterService = Service::factory()->create([
            'name' => 'Later Inactive Add',
            'is_active' => false,
        ]);

        $package->services()->attach([
            $reactivatedService->id => ['sort_order' => 3],
            $deactivatedLaterService->id => ['sort_order' => 4],
        ]);

        $employee->serviceAssignments()->createMany([
            ['venue_id' => $venue->id, 'service_id' => $reactivatedService->id],
            ['venue_id' => $venue->id, 'service_id' => $deactivatedLaterService->id],
        ]);
        $employee->packageServiceAssignments()->createMany([
            ['venue_id' => $venue->id, 'package_id' => $package->id, 'service_id' => $reactivatedService->id],
            ['venue_id' => $venue->id, 'package_id' => $package->id, 'service_id' => $deactivatedLaterService->id],
        ]);

        $response = $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.edit', $functionEntry));

        $response->assertOk()
            ->assertSee('Later Active Add')
            ->assertDontSee('Later Inactive Add');

        $functionPackage = $functionEntry->fresh()->packages()->with('serviceLines')->firstOrFail();
        $this->assertNotNull($functionPackage->serviceLines->firstWhere('service_id', $reactivatedService->id));
        $this->assertNull($functionPackage->serviceLines->firstWhere('service_id', $deactivatedLaterService->id));
    }

    public function test_frozen_fund_is_not_applied_for_employee_b(): void
    {
        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue, 99999);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-03-30',
                'name' => 'No Frozen Fund',
            ])->assertRedirect();

        $functionEntry = FunctionEntry::firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.extra-charges.store', $functionEntry), [
                'entry_date' => '2026-03-30',
                'name' => 'Charge',
                'mode' => 'cash',
                'amount' => '100.00',
            ])->assertRedirect();

        $functionEntry->refresh();

        $this->assertSame(0, (int) $functionEntry->frozen_fund_minor);
        $this->assertSame(10000, (int) $functionEntry->net_total_after_frozen_fund_minor);
    }

    public function test_employee_can_preview_and_download_attachment_linked_to_extra_charge(): void
    {
        Storage::fake('local');

        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $functionEntry = FunctionEntry::query()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-30',
            'name' => 'Attachment Access',
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.extra-charges.store', $functionEntry), [
                'entry_date' => '2026-03-30',
                'name' => 'Receipt',
                'mode' => 'cash',
                'amount' => '10.00',
                'attachments' => [
                    UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
                ],
            ])->assertRedirect();

        $extraCharge = FunctionExtraCharge::firstOrFail();
        $attachment = Attachment::query()
            ->where('attachable_type', FunctionExtraCharge::class)
            ->where('attachable_id', $extraCharge->id)
            ->firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.attachments.preview', [$functionEntry, $attachment]))
            ->assertOk();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.attachments.download', [$functionEntry, $attachment]))
            ->assertOk();
    }

    public function test_function_index_paginates_after_fifty_entries_but_print_mode_shows_all_filtered_rows(): void
    {
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        FunctionEntry::factory()->count(51)->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-30',
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.index'))
            ->assertOk()
            ->assertSee('?page=2', false);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.index', ['print' => 1]))
            ->assertOk()
            ->assertSee('51 total entries');
    }

    public function test_employee_can_export_function_register_to_excel_without_currency_symbols(): void
    {
        Storage::fake('local');

        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create(['name' => 'Garden Court']);
        $this->assignEmployeeToVenue($employee, $venue, 10000);

        $service = Service::factory()->create(['name' => 'Export Photography']);
        $package = Package::factory()->create(['name' => 'Export Package']);
        $package->services()->attach($service->id, ['sort_order' => 1]);

        $entry = FunctionEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-30',
            'name' => 'March Function',
            'function_total_minor' => 25000,
            'paid_total_minor' => 10000,
            'pending_total_minor' => 15000,
            'frozen_fund_minor' => 10000,
            'net_total_after_frozen_fund_minor' => 15000,
        ]);
        $functionPackage = $entry->packages()->create([
            'package_id' => $package->id,
            'name_snapshot' => 'Export Package',
            'code_snapshot' => 'EXP-PKG',
            'total_minor' => 25000,
        ]);
        $functionPackage->serviceLines()->create([
            'service_id' => $service->id,
            'sort_order' => 1,
            'is_selected' => true,
            'item_name_snapshot' => 'Export Photography',
            'rate_minor' => 25000,
            'person_input_mode' => 'fixed',
            'persons' => 1,
            'extra_charge_minor' => 0,
            'line_total_minor' => 25000,
        ]);
        $serviceAttachment = $service->attachments()->create([
            'uploaded_by' => $employee->id,
            'disk' => 'local',
            'storage_path' => 'attachments/service-export-guide.pdf',
            'original_name' => 'service-export-guide.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1200,
        ]);

        Excel::fake();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.export', ['entry_date' => '2026-03-30']))
            ->assertOk();

        Excel::assertDownloaded('function-register-export.xlsx', function ($export) use ($entry, $serviceAttachment) {
            $this->assertInstanceOf(WorkbookExport::class, $export);

            $sheets = $export->sheets();
            $this->assertCount(3, $sheets);

            $serialized = json_encode([
                $sheets[0]->array(),
                $sheets[1]->array(),
                $sheets[2]->array(),
            ], JSON_THROW_ON_ERROR);

            $this->assertStringNotContainsString('â‚¹', $serialized);
            $this->assertStringNotContainsString('Rs', $serialized);
            $this->assertStringNotContainsString('INR', $serialized);
            $this->assertStringNotContainsString('USD', $serialized);
            $this->assertSame('Function Total', $sheets[0]->array()[7][0]);
            $this->assertSame('Entry Date', $sheets[1]->array()[0][0]);
            $this->assertContains('Service Attachment Names', $sheets[1]->array()[0]);
            $this->assertContains('Service Attachment Download URLs', $sheets[1]->array()[0]);
            $this->assertStringContainsString('service-export-guide.pdf', $serialized);
            $this->assertStringContainsString(
                str_replace('/', '\\/', (string) route('employee.functions.attachments.download', [$entry, $serviceAttachment])),
                $serialized
            );

            return true;
        });
    }

    public function test_function_index_shows_child_record_counts_in_the_register_table(): void
    {
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue, 10000);

        $entry = FunctionEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-30',
            'name' => 'Counted Function',
        ]);

        $package = Package::factory()->create();

        $entry->packages()->create([
            'package_id' => $package->id,
            'name_snapshot' => 'Wedding Package',
        ]);

        $entry->extraCharges()->create([
            'entry_date' => '2026-03-30',
            'name' => 'Flowers',
            'mode' => 'cash',
            'amount_minor' => 1000,
        ]);

        $entry->discounts()->create([
            'entry_date' => '2026-03-30',
            'name' => 'Promo',
            'mode' => 'cash',
            'amount_minor' => 500,
        ]);

        $entry->installments()->create([
            'entry_date' => '2026-03-30',
            'name' => 'Advance',
            'mode' => 'cash',
            'amount_minor' => 1500,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.index'))
            ->assertOk()
            ->assertSee('Counted Function')
            ->assertSeeInOrder(['Counted Function', '1', '1', '1', '1']);
    }

    public function test_employee_can_open_date_print_view_with_child_rows_terms_signatures_and_attachment_links(): void
    {
        Storage::fake('local');

        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create(['name' => 'Garden Court']);
        $this->assignEmployeeToVenue($employee, $venue, 12000);

        $service = Service::factory()->create(['name' => 'Photography']);
        $service->update(['notes' => 'Bring backup camera and flash kit.']);
        $hiddenService = Service::factory()->create(['name' => 'Hidden Catering']);
        $package = Package::factory()->create([
            'name' => 'Wedding Prime',
            'code' => 'WED-PRIME',
            'description' => 'Package note for wedding prime.',
        ]);
        $package->services()->attach([
            $service->id => ['sort_order' => 1],
            $hiddenService->id => ['sort_order' => 2],
        ]);

        $entry = FunctionEntry::query()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-30',
            'name' => 'March Wedding',
            'notes' => 'Print all details',
            'package_total_minor' => 24000,
            'extra_charge_total_minor' => 2500,
            'discount_total_minor' => 1000,
            'function_total_minor' => 25500,
            'paid_total_minor' => 8000,
            'pending_total_minor' => 17500,
            'frozen_fund_minor' => 12000,
            'net_total_after_frozen_fund_minor' => 13500,
        ]);

        $functionPackage = $entry->packages()->create([
            'package_id' => $package->id,
            'name_snapshot' => 'Wedding Prime',
            'code_snapshot' => 'WED-PRIME',
            'total_minor' => 24000,
        ]);

        $functionPackage->serviceLines()->create([
            'service_id' => $service->id,
            'sort_order' => 1,
            'is_selected' => true,
            'item_name_snapshot' => 'Photography',
            'rate_minor' => 12000,
            'person_input_mode' => 'fixed',
            'persons' => 2,
            'extra_charge_minor' => 0,
            'notes' => 'Lead crew',
            'line_total_minor' => 24000,
        ]);
        $functionPackage->serviceLines()->create([
            'service_id' => $hiddenService->id,
            'sort_order' => 2,
            'is_selected' => false,
            'item_name_snapshot' => 'Hidden Catering',
            'rate_minor' => 5000,
            'person_input_mode' => 'none',
            'persons' => 0,
            'extra_charge_minor' => 0,
            'notes' => 'Should stay hidden in print',
            'line_total_minor' => 0,
        ]);
        $serviceAttachment = $service->attachments()->create([
            'uploaded_by' => $employee->id,
            'disk' => 'local',
            'storage_path' => 'attachments/service-brief.pdf',
            'original_name' => 'service-brief.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 850,
        ]);

        $extraCharge = $entry->extraCharges()->create([
            'entry_date' => '2026-03-30',
            'name' => 'Flowers',
            'mode' => 'cash',
            'amount_minor' => 2500,
            'note' => 'Fresh decor flowers',
        ]);

        $installment = $entry->installments()->create([
            'entry_date' => '2026-03-30',
            'name' => 'Advance',
            'mode' => 'bank',
            'amount_minor' => 8000,
            'note' => 'First payment',
        ]);

        $discount = $entry->discounts()->create([
            'entry_date' => '2026-03-30',
            'name' => 'Promo',
            'mode' => 'cash',
            'amount_minor' => 1000,
            'note' => 'Manual offer',
        ]);

        $baseAttachment = $entry->attachments()->create([
            'uploaded_by' => $employee->id,
            'disk' => 'local',
            'storage_path' => 'attachments/base-brief.pdf',
            'original_name' => 'base-brief.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1200,
        ]);

        $extraAttachment = $extraCharge->attachments()->create([
            'uploaded_by' => $employee->id,
            'disk' => 'local',
            'storage_path' => 'attachments/extra-proof.pdf',
            'original_name' => 'extra-proof.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 900,
        ]);

        $installment->attachments()->create([
            'uploaded_by' => $employee->id,
            'disk' => 'local',
            'storage_path' => 'attachments/installment-slip.pdf',
            'original_name' => 'installment-slip.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 800,
        ]);

        PrintSetting::current()->update([
            'function_terms_and_conditions' => "1. Dummy terms.\n2. Manager sign after review.",
        ]);

        $response = $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.print-date', ['entryDate' => '2026-03-30']));

        $response->assertOk()
            ->assertSee('Date Print Sheet')
            ->assertSee('March Wedding')
            ->assertSee('Wedding Prime')
            ->assertSee('Package note for wedding prime.')
            ->assertSee('Photography')
            ->assertSee('Bring backup camera and flash kit.')
            ->assertSee('Entry notes: Lead crew')
            ->assertDontSee('<th style="width: 12%">Rate</th>', false)
            ->assertDontSee('<th style="width: 15%">Line total</th>', false)
            ->assertDontSee('Hidden Catering')
            ->assertSee('Flowers')
            ->assertSee('Advance')
            ->assertSee('Promo')
            ->assertSee('Dummy terms.')
            ->assertSee('Customer Signature')
            ->assertSee('Manager Signature')
            ->assertSee('service-brief.pdf')
            ->assertSee('base-brief.pdf')
            ->assertSee('extra-proof.pdf')
            ->assertSee(route('employee.functions.attachments.download', [$entry, $serviceAttachment]), false)
            ->assertSee(route('employee.functions.attachments.download', [$entry, $baseAttachment]), false)
            ->assertSee(route('employee.functions.attachments.download', [$entry, $extraAttachment]), false);
    }

    public function test_existing_function_package_picks_up_newly_assigned_package_services_in_edit_view(): void
    {
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $serviceA = Service::factory()->create(['name' => 'Stage Lights']);
        $serviceB = Service::factory()->create(['name' => 'Drone Coverage']);
        $package = Package::factory()->create(['name' => 'Growth Package']);
        $package->services()->attach([
            $serviceA->id => ['sort_order' => 1],
            $serviceB->id => ['sort_order' => 2],
        ]);

        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $serviceA->id,
        ]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $serviceA->id,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-04-10',
                'name' => 'Service Sync Test',
            ])->assertRedirect();

        $functionEntry = FunctionEntry::firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])->assertRedirect();

        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $serviceB->id,
        ]);
        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $serviceB->id,
        ]);

        $response = $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.edit', $functionEntry));

        $response->assertOk()->assertSee('Drone Coverage');

        $functionPackage = $functionEntry->fresh()->packages()->with('serviceLines')->firstOrFail();
        $this->assertCount(2, $functionPackage->serviceLines);
        $this->assertNotNull($functionPackage->serviceLines->firstWhere('service_id', $serviceB->id));
    }

    public function test_existing_function_package_resyncs_service_rate_and_totals_when_master_service_rate_changes(): void
    {
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $service = Service::factory()->create([
            'name' => 'Resynced Service',
            'standard_rate_minor' => 200000,
            'uses_persons' => false,
            'person_input_mode' => 'none',
            'default_persons' => null,
        ]);
        $package = Package::factory()->create(['name' => 'Resynced Package']);
        $package->services()->attach($service->id, ['sort_order' => 1]);

        $employee->serviceAssignments()->create([
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);
        $employee->packageAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        $employee->packageServiceAssignments()->create([
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-04-16',
                'name' => 'Rate Sync Function',
            ])->assertRedirect();

        $functionEntry = FunctionEntry::firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])->assertRedirect();

        $functionPackage = $functionEntry->fresh()->packages()->with('serviceLines')->firstOrFail();
        $serviceLine = $functionPackage->serviceLines->firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.functions.packages.update', [$functionEntry, $functionPackage]), [
                'service_lines' => [
                    $serviceLine->id => [
                        'is_selected' => '1',
                        'rate' => '2000.00',
                        'extra_charge' => '0.00',
                        'notes' => 'Initial saved line',
                    ],
                ],
            ])->assertRedirect();

        $service->update([
            'standard_rate_minor' => 300000,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.edit', $functionEntry))
            ->assertOk()
            ->assertDontSee('<th>Rate</th>', false)
            ->assertDontSee('<th>Row Total</th>', false);

        $functionEntry->refresh();
        $functionPackage = $functionEntry->packages()->with('serviceLines')->firstOrFail();
        $serviceLine = $functionPackage->serviceLines->firstOrFail();

        $this->assertSame(300000, (int) $serviceLine->rate_minor);
        $this->assertSame(300000, (int) $serviceLine->line_total_minor);
        $this->assertSame(300000, (int) $functionPackage->total_minor);
        $this->assertSame(300000, (int) $functionEntry->package_total_minor);
        $this->assertSame(300000, (int) $functionEntry->function_total_minor);
    }

    public function test_employee_can_preview_and_download_service_attachment_linked_through_function_package(): void
    {
        Storage::fake('local');

        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $service = Service::factory()->create(['name' => 'Lighting Guide']);
        Storage::disk('local')->put('attachments/service-lighting-guide.pdf', 'service guide');
        $serviceAttachment = $service->attachments()->create([
            'uploaded_by' => $employee->id,
            'disk' => 'local',
            'storage_path' => 'attachments/service-lighting-guide.pdf',
            'original_name' => 'service-lighting-guide.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 640,
        ]);
        $package = Package::factory()->create();
        $functionEntry = FunctionEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-31',
        ]);
        $functionPackage = $functionEntry->packages()->create([
            'package_id' => $package->id,
            'name_snapshot' => 'Lighting Package',
            'total_minor' => 9000,
        ]);
        $functionPackage->serviceLines()->create([
            'service_id' => $service->id,
            'sort_order' => 1,
            'is_selected' => true,
            'item_name_snapshot' => 'Lighting Guide',
            'rate_minor' => 9000,
            'person_input_mode' => 'none',
            'persons' => 0,
            'extra_charge_minor' => 0,
            'line_total_minor' => 9000,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.attachments.preview', [$functionEntry, $serviceAttachment]))
            ->assertOk();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.attachments.download', [$functionEntry, $serviceAttachment]))
            ->assertOk();
    }

    private function assignEmployeeToVenue(User $employee, Venue $venue, int $frozenFundMinor = 0): void
    {
        $employee->venues()->attach($venue->id, [
            'frozen_fund_minor' => $frozenFundMinor,
        ]);
    }
}
