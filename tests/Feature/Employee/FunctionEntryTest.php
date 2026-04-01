<?php

namespace Tests\Feature\Employee;

use App\Exports\Reports\WorkbookExport;
use App\Models\Attachment;
use App\Models\FunctionEntry;
use App\Models\FunctionExtraCharge;
use App\Models\Package;
use App\Models\Service;
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

        $serviceA = Service::factory()->create(['standard_rate_minor' => 10000]);
        $serviceB = Service::factory()->create(['standard_rate_minor' => 5000]);
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
                        'persons' => 2,
                        'rate' => '100.00',
                        'extra_charge' => '25.00',
                        'notes' => 'Primary service',
                    ],
                    $lineB->id => [
                        'is_selected' => '1',
                        'persons' => 1,
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
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create(['name' => 'Garden Court']);
        $this->assignEmployeeToVenue($employee, $venue, 10000);

        FunctionEntry::factory()->create([
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

        Excel::fake();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.export', ['entry_date' => '2026-03-30']))
            ->assertOk();

        Excel::assertDownloaded('function-register-export.xlsx', function ($export) {
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

            return true;
        });
    }

    private function assignEmployeeToVenue(User $employee, Venue $venue, int $frozenFundMinor = 0): void
    {
        $employee->venues()->attach($venue->id, [
            'frozen_fund_minor' => $frozenFundMinor,
        ]);
    }
}
