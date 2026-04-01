<?php

namespace Tests\Feature\Admin;

use App\Models\AdminIncomeEntry;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminIncomeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_admin_income_entries_with_attachments(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.admin-income.store'), [
                'entry_date' => '2026-03-31',
                'name' => 'Owner collection',
                'amount' => '220.00',
                'notes' => 'Admin income row',
                'attachments' => [
                    UploadedFile::fake()->create('admin-income.pdf', 120, 'application/pdf'),
                ],
            ])->assertRedirect();

        $entry = AdminIncomeEntry::firstOrFail();

        $this->assertDatabaseHas('admin_income_entries', [
            'id' => $entry->id,
            'user_id' => $admin->id,
            'amount_minor' => 22000,
        ]);

        $attachment = Attachment::query()
            ->where('attachable_type', AdminIncomeEntry::class)
            ->where('attachable_id', $entry->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.admin-income.index'))
            ->assertOk()
            ->assertSee('220.00');

        $this->actingAs($admin)
            ->get(route('admin.admin-income.attachments.preview', ['adminIncome' => $entry, 'attachment' => $attachment]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.admin-income.attachments.download', ['adminIncome' => $entry, 'attachment' => $attachment]))
            ->assertOk();
    }

    public function test_admin_income_create_and_edit_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $entry = AdminIncomeEntry::factory()->create([
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.admin-income.create'))
            ->assertOk()
            ->assertSee('Back to list');

        $this->actingAs($admin)
            ->get(route('admin.admin-income.edit', $entry))
            ->assertOk()
            ->assertSee('Back to list');
    }

    public function test_employee_cannot_access_admin_income_routes(): void
    {
        $employee = User::factory()->employeeA()->create();

        $this->actingAs($employee)
            ->get(route('admin.admin-income.index'))
            ->assertForbidden();
    }
}
