<?php

namespace Tests\Feature\Employee;

use App\Models\Attachment;
use App\Models\FunctionEntry;
use App\Models\FunctionPackage;
use App\Models\Package;
use App\Models\PackageAssignment;
use App\Models\PackageServiceAssignment;
use App\Models\Service;
use App\Models\ServiceAssignment;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FunctionEntryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_complete_function_entry_action_center_and_totals_are_recalculated(): void
    {
        Storage::fake('local');

        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 10000]);

        $service = Service::factory()->create([
            'standard_rate_minor' => 25000,
            'person_input_mode' => 'employee',
            'uses_persons' => true,
            'default_persons' => null,
        ]);
        $package = Package::factory()->create();
        $package->services()->attach($service->id, ['sort_order' => 1]);

        ServiceAssignment::create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'service_id' => $service->id,
        ]);
        PackageAssignment::create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
        ]);
        PackageServiceAssignment::create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'package_id' => $package->id,
            'service_id' => $service->id,
        ]);

        $response = $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-03-30',
                'name' => 'North Hall Wedding',
                'notes' => 'Base function note',
                'attachments' => [
                    UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect(route('employee.functions.index'));

        $functionEntry = FunctionEntry::query()->firstOrFail();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => FunctionEntry::class,
            'attachable_id' => $functionEntry->id,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'packages']));

        $functionPackage = FunctionPackage::query()->firstOrFail();
        $serviceLine = $functionPackage->serviceLines()->firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.functions.packages.update', [$functionEntry, $functionPackage]), [
                'service_lines' => [
                    $serviceLine->id => [
                        'is_selected' => '1',
                        'persons' => 10,
                        'rate' => '250.00',
                        'extra_charge' => '50.00',
                        'notes' => 'Late night coverage',
                    ],
                ],
            ])
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'packages']));

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.extra-charges.store', $functionEntry), [
                'entry_date' => '2026-03-30',
                'name' => 'Generator',
                'mode' => 'cash',
                'amount' => '100.00',
                'note' => 'Additional power',
                'attachments' => [
                    UploadedFile::fake()->create('generator.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'extra-charges']));

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.installments.store', $functionEntry), [
                'entry_date' => '2026-03-30',
                'name' => 'Advance',
                'mode' => 'upi',
                'amount' => '200.00',
                'note' => 'Advance received',
            ])
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'installments']));

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.discounts.store', $functionEntry), [
                'entry_date' => '2026-03-30',
                'name' => 'Referral',
                'mode' => 'other',
                'amount' => '75.00',
                'note' => 'Referral discount',
            ])
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'discounts']));

        $functionEntry->refresh();

        $this->assertSame(255000, (int) $functionEntry->package_total_minor);
        $this->assertSame(10000, (int) $functionEntry->extra_charge_total_minor);
        $this->assertSame(7500, (int) $functionEntry->discount_total_minor);
        $this->assertSame(257500, (int) $functionEntry->function_total_minor);
        $this->assertSame(20000, (int) $functionEntry->paid_total_minor);
        $this->assertSame(237500, (int) $functionEntry->pending_total_minor);
        $this->assertSame(10000, (int) $functionEntry->frozen_fund_minor);
        $this->assertSame(247500, (int) $functionEntry->net_total_after_frozen_fund_minor);

        $attachment = Attachment::query()->where('attachable_type', FunctionEntry::class)->firstOrFail();

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get(route('employee.functions.attachments.download', [$functionEntry, $attachment]))
            ->assertOk();
    }

    public function test_employee_cannot_view_function_entry_outside_selected_venue(): void
    {
        $employee = User::factory()->employeeB()->create();
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        $employee->venues()->attach($venueA->id);
        $employee->venues()->attach($venueB->id);

        $functionEntry = FunctionEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venueA->id,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venueB->id])
            ->get(route('employee.functions.edit', $functionEntry))
            ->assertStatus(404);
    }

    public function test_employee_cannot_add_unassigned_package_to_function_entry(): void
    {
        $employee = User::factory()->employeeC()->create();
        $venue = Venue::factory()->create();
        $employee->venues()->attach($venue->id);

        $package = Package::factory()->create();
        $functionEntry = FunctionEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.packages.store', $functionEntry), [
                'package_id' => $package->id,
            ])
            ->assertNotFound();
    }

    public function test_non_type_a_employee_does_not_apply_frozen_fund_to_totals(): void
    {
        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create();
        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 50000]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->post(route('employee.functions.store'), [
                'entry_date' => '2026-03-30',
                'name' => 'No frozen fund here',
                'notes' => 'Employee B should ignore frozen fund',
            ])
            ->assertRedirect();

        $functionEntry = FunctionEntry::query()->firstOrFail();

        $this->assertSame(0, (int) $functionEntry->frozen_fund_minor);
        $this->assertSame(0, (int) $functionEntry->net_total_after_frozen_fund_minor);
    }

    public function test_employee_can_edit_and_delete_extra_charges_installments_and_discounts_from_action_center(): void
    {
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $employee->venues()->attach($venue->id, ['frozen_fund_minor' => 0]);

        $functionEntry = FunctionEntry::factory()->create([
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'entry_date' => '2026-04-16',
        ]);

        $extraCharge = $functionEntry->extraCharges()->create([
            'entry_date' => '2026-04-16',
            'name' => 'Generator',
            'mode' => 'cash',
            'amount_minor' => 10000,
            'note' => 'Original extra charge',
        ]);
        $installment = $functionEntry->installments()->create([
            'entry_date' => '2026-04-16',
            'name' => 'Advance',
            'mode' => 'upi',
            'amount_minor' => 20000,
            'note' => 'Original installment',
        ]);
        $discount = $functionEntry->discounts()->create([
            'entry_date' => '2026-04-16',
            'name' => 'Referral',
            'mode' => 'other',
            'amount_minor' => 5000,
            'note' => 'Original discount',
        ]);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.functions.extra-charges.update', [$functionEntry, $extraCharge]), [
                'entry_date' => '2026-04-17',
                'name' => 'Generator Updated',
                'mode' => 'card',
                'amount' => '150.00',
                'note' => 'Updated extra charge',
            ])
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'extra-charges']));

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.functions.installments.update', [$functionEntry, $installment]), [
                'entry_date' => '2026-04-17',
                'name' => 'Advance Updated',
                'mode' => 'cash',
                'amount' => '250.00',
                'note' => 'Updated installment',
            ])
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'installments']));

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->put(route('employee.functions.discounts.update', [$functionEntry, $discount]), [
                'entry_date' => '2026-04-17',
                'name' => 'Referral Updated',
                'mode' => 'cash',
                'amount' => '75.00',
                'note' => 'Updated discount',
            ])
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'discounts']));

        $this->assertDatabaseHas('function_extra_charges', [
            'id' => $extraCharge->id,
            'name' => 'Generator Updated',
            'mode' => 'card',
            'amount_minor' => 15000,
            'note' => 'Updated extra charge',
        ]);
        $this->assertDatabaseHas('function_installments', [
            'id' => $installment->id,
            'name' => 'Advance Updated',
            'mode' => 'cash',
            'amount_minor' => 25000,
            'note' => 'Updated installment',
        ]);
        $this->assertDatabaseHas('function_discounts', [
            'id' => $discount->id,
            'name' => 'Referral Updated',
            'mode' => 'cash',
            'amount_minor' => 7500,
            'note' => 'Updated discount',
        ]);

        $functionEntry->refresh();
        $this->assertSame(15000, (int) $functionEntry->extra_charge_total_minor);
        $this->assertSame(25000, (int) $functionEntry->paid_total_minor);
        $this->assertSame(7500, (int) $functionEntry->discount_total_minor);

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->delete(route('employee.functions.extra-charges.destroy', [$functionEntry, $extraCharge]))
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'extra-charges']));

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->delete(route('employee.functions.installments.destroy', [$functionEntry, $installment]))
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'installments']));

        $this->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->delete(route('employee.functions.discounts.destroy', [$functionEntry, $discount]))
            ->assertRedirect(route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'discounts']));

        $this->assertDatabaseMissing('function_extra_charges', ['id' => $extraCharge->id]);
        $this->assertDatabaseMissing('function_installments', ['id' => $installment->id]);
        $this->assertDatabaseMissing('function_discounts', ['id' => $discount->id]);

        $functionEntry->refresh();
        $this->assertSame(0, (int) $functionEntry->extra_charge_total_minor);
        $this->assertSame(0, (int) $functionEntry->paid_total_minor);
        $this->assertSame(0, (int) $functionEntry->discount_total_minor);
    }
}
