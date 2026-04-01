<?php

namespace Tests\Feature\Employee;

use App\Exports\Reports\WorkbookExport;
use App\Models\Attachment;
use App\Models\DailyBillingEntry;
use App\Models\DailyIncomeEntry;
use App\Models\User;
use App\Models\VendorEntry;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LedgerEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_a_can_open_daily_income_and_billing_create_pages(): void
    {
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-income.create'))
            ->assertOk()
            ->assertSee('Create Daily Income Entry');

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-billing.create'))
            ->assertOk()
            ->assertSee('Create Daily Billing Entry');
    }

    public function test_employee_a_can_create_daily_income_with_totals_and_attachments(): void
    {
        Storage::fake('local');

        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.daily-income.store'), [
                'entry_date' => '2026-03-31',
                'name' => 'Hall collection',
                'amount' => '150.50',
                'notes' => 'Daily collection',
                'attachments' => [
                    UploadedFile::fake()->create('income-proof.pdf', 120, 'application/pdf'),
                ],
            ])->assertRedirect(route('employee.daily-income.index'));

        $entry = DailyIncomeEntry::firstOrFail();

        $this->assertDatabaseHas('daily_income_entries', [
            'id' => $entry->id,
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'amount_minor' => 15050,
        ]);

        $attachment = Attachment::query()
            ->where('attachable_type', DailyIncomeEntry::class)
            ->where('attachable_id', $entry->id)
            ->firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-income.index'))
            ->assertOk()
            ->assertSee('150.50');

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-income.attachments.preview', ['dailyIncome' => $entry, 'attachment' => $attachment]))
            ->assertOk();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-income.attachments.download', ['dailyIncome' => $entry, 'attachment' => $attachment]))
            ->assertOk();
    }

    public function test_employee_a_can_create_daily_billing_entry(): void
    {
        Storage::fake('local');

        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.daily-billing.store'), [
                'entry_date' => '2026-03-31',
                'name' => 'Hall billing',
                'amount' => '225.75',
                'notes' => 'Daily expense',
                'attachments' => [
                    UploadedFile::fake()->create('billing-proof.pdf', 120, 'application/pdf'),
                ],
            ])->assertRedirect(route('employee.daily-billing.index'));

        $entry = DailyBillingEntry::firstOrFail();

        $this->assertDatabaseHas('daily_billing_entries', [
            'id' => $entry->id,
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'amount_minor' => 22575,
        ]);
    }

    public function test_employee_c_cannot_access_phase_four_employee_ledgers(): void
    {
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-income.index'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-billing.index'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.vendor-entries.index'))
            ->assertForbidden();
    }

    public function test_employee_b_can_create_vendor_entries_and_see_vendor_totals(): void
    {
        Storage::fake('local');

        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create();
        $venue->syncVendorSlots([
            1 => 'Lights',
            2 => 'Sound',
            3 => 'Catering',
            4 => 'Flowers',
        ]);
        $venue->load('vendors');
        $lights = $venue->vendors->firstWhere('slot_number', 1);
        $sound = $venue->vendors->firstWhere('slot_number', 2);
        $this->assignEmployeeToVenue($employee, $venue);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.vendor-entries.create'))
            ->assertOk()
            ->assertSee('Create Vendor Entry')
            ->assertSee('Lights')
            ->assertSee('Sound');

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.vendor-entries.store'), [
                'entry_date' => '2026-03-31',
                'venue_vendor_id' => $lights->id,
                'name' => 'Lights advance',
                'amount' => '100.00',
                'notes' => 'Vendor payment',
                'attachments' => [
                    UploadedFile::fake()->create('lights.jpg', 80, 'image/jpeg'),
                ],
            ])->assertRedirect(route('employee.vendor-entries.index'));

        $this->assertDatabaseHas('vendor_entries', [
            'venue_vendor_id' => $lights->id,
            'vendor_name_snapshot' => 'Lights',
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.vendor-entries.store'), [
                'entry_date' => '2026-03-31',
                'venue_vendor_id' => $sound->id,
                'name' => 'Sound advance',
                'amount' => '75.00',
                'notes' => 'Vendor payment',
            ])->assertRedirect(route('employee.vendor-entries.index'));

        $entry = VendorEntry::query()->where('venue_vendor_id', $lights->id)->firstOrFail();
        $attachment = Attachment::query()
            ->where('attachable_type', VendorEntry::class)
            ->where('attachable_id', $entry->id)
            ->firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.vendor-entries.index'))
            ->assertOk()
            ->assertSee('Lights')
            ->assertSee('100.00')
            ->assertSee('75.00');

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.vendor-entries.attachments.preview', ['vendorEntry' => $entry, 'attachment' => $attachment]))
            ->assertOk();
    }

    public function test_employee_b_can_rename_current_venue_vendor_slot_and_existing_entries_follow_new_name(): void
    {
        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create();
        $venue->syncVendorSlots([
            1 => 'Vendor One',
            2 => 'Vendor Two',
            3 => 'Vendor Three',
            4 => 'Vendor Four',
        ]);
        $venue->load('vendors');
        $vendor = $venue->vendors->firstWhere('slot_number', 1);
        $this->assignEmployeeToVenue($employee, $venue);

        $entry = VendorEntry::query()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'venue_vendor_id' => $vendor->id,
            'vendor_name_snapshot' => $vendor->name,
            'entry_date' => '2026-03-31',
            'name' => 'Vendor advance',
            'amount_minor' => 10000,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.vendor-entries.vendors.update', $vendor), [
                'name' => 'Main Lights',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('venue_vendors', [
            'id' => $vendor->id,
            'name' => 'Main Lights',
        ]);

        $this->assertDatabaseHas('vendor_entries', [
            'id' => $entry->id,
            'vendor_name_snapshot' => 'Main Lights',
        ]);
    }

    public function test_employee_cannot_edit_another_employees_daily_billing_entry(): void
    {
        $venue = Venue::factory()->create();
        $employee = User::factory()->employeeA()->create();
        $otherEmployee = User::factory()->employeeB()->create();
        $this->assignEmployeeToVenue($employee, $venue);
        $this->assignEmployeeToVenue($otherEmployee, $venue);

        $entry = DailyBillingEntry::query()->create([
            'user_id' => $otherEmployee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-31',
            'name' => 'Other billing',
            'amount_minor' => 20000,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-billing.edit', ['dailyBilling' => $entry]))
            ->assertForbidden();
    }

    public function test_ledger_indexes_paginate_after_fifty_entries_and_print_mode_shows_all_rows(): void
    {
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $this->assignEmployeeToVenue($employee, $venue);

        DailyIncomeEntry::factory()->count(51)->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-31',
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-income.index'))
            ->assertOk()
            ->assertSee('?page=2', false);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-income.index', ['print' => 1]))
            ->assertOk()
            ->assertSee('51 total entries');
    }

    public function test_employee_can_export_daily_income_and_billing_registers_to_excel(): void
    {
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create(['name' => 'Sky Hall']);
        $this->assignEmployeeToVenue($employee, $venue);

        DailyIncomeEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-31',
            'name' => 'Income Row',
            'amount_minor' => 12345,
        ]);

        DailyBillingEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-03-31',
            'name' => 'Billing Row',
            'amount_minor' => 67890,
        ]);

        Excel::fake();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-income.export', ['entry_date' => '2026-03-31']))
            ->assertOk();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.daily-billing.export', ['entry_date' => '2026-03-31']))
            ->assertOk();

        Excel::assertDownloaded('daily-income-register-export.xlsx', function ($export) {
            $this->assertInstanceOf(WorkbookExport::class, $export);
            $serialized = json_encode([$export->sheets()[0]->array(), $export->sheets()[1]->array()], JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('â‚¹', $serialized);
            $this->assertStringNotContainsString('INR', $serialized);
            $this->assertSame('Amount', $export->sheets()[0]->array()[9][0]);

            return true;
        });

        Excel::assertDownloaded('daily-billing-register-export.xlsx', function ($export) {
            $this->assertInstanceOf(WorkbookExport::class, $export);
            $serialized = json_encode([$export->sheets()[0]->array(), $export->sheets()[1]->array()], JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('â‚¹', $serialized);
            $this->assertStringNotContainsString('INR', $serialized);
            $this->assertSame('Entry Date', $export->sheets()[1]->array()[0][0]);

            return true;
        });
    }

    public function test_employee_b_can_export_vendor_register_to_excel_with_vendor_totals(): void
    {
        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create(['name' => 'Lake View']);
        $venue->syncVendorSlots([
            1 => 'Vendor One',
            2 => 'Vendor Two',
            3 => 'Vendor Three',
            4 => 'Vendor Four',
        ]);
        $venue->load('vendors');
        $vendor = $venue->vendors->firstWhere('slot_number', 1);
        $this->assignEmployeeToVenue($employee, $venue);

        VendorEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'venue_vendor_id' => $vendor->id,
            'vendor_name_snapshot' => 'Vendor One',
            'entry_date' => '2026-03-31',
            'name' => 'Vendor Row',
            'amount_minor' => 50500,
        ]);

        Excel::fake();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.vendor-entries.export', ['venue_vendor_id' => $vendor->id]))
            ->assertOk();

        Excel::assertDownloaded('vendor-register-export.xlsx', function ($export) {
            $this->assertInstanceOf(WorkbookExport::class, $export);
            $this->assertCount(4, $export->sheets());

            $serialized = json_encode([
                $export->sheets()[0]->array(),
                $export->sheets()[1]->array(),
                $export->sheets()[2]->array(),
                $export->sheets()[3]->array(),
            ], JSON_THROW_ON_ERROR);

            $this->assertStringNotContainsString('â‚¹', $serialized);
            $this->assertStringNotContainsString('Rs', $serialized);
            $this->assertSame('Vendor', $export->sheets()[3]->array()[0][0]);

            return true;
        });
    }

    private function assignEmployeeToVenue(User $employee, Venue $venue): void
    {
        $employee->venues()->attach($venue->id, [
            'frozen_fund_minor' => 0,
        ]);
    }
}
