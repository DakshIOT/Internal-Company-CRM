<?php

namespace Tests\Feature\Admin;

use App\Exports\Reports\WorkbookExport;
use App\Models\AdminIncomeEntry;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
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

    public function test_admin_can_remove_admin_income_attachment_from_edit_page(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $entry = AdminIncomeEntry::factory()->create([
            'user_id' => $admin->id,
        ]);
        $attachment = $entry->attachments()->create([
            'uploaded_by' => $admin->id,
            'disk' => 'local',
            'storage_path' => 'attachments/admin-income/remove-me.pdf',
            'original_name' => 'remove-me.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.admin-income.edit', $entry))
            ->assertOk()
            ->assertSee('form="attachment-delete-'.$attachment->id.'"', false)
            ->assertSee('id="attachment-delete-'.$attachment->id.'"', false);

        $this->actingAs($admin)
            ->delete(route('admin.admin-income.attachments.destroy', [
                'adminIncome' => $entry,
                'attachment' => $attachment,
            ]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Attachment removed.');

        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment->id,
        ]);
    }

    public function test_admin_income_index_exposes_export_button_and_export_includes_attachment_names_and_urls(): void
    {
        $admin = User::factory()->admin()->create();
        $entry = AdminIncomeEntry::factory()->create([
            'user_id' => $admin->id,
            'entry_date' => '2026-03-31',
            'name' => 'Head office transfer',
            'amount_minor' => 22000,
        ]);

        $attachment = $entry->attachments()->create([
            'uploaded_by' => $admin->id,
            'disk' => 'local',
            'storage_path' => 'attachments/admin-income-proof.pdf',
            'original_name' => 'admin-income-proof.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.admin-income.index'))
            ->assertOk()
            ->assertSee('Export Excel');

        Excel::fake();

        $this->actingAs($admin)
            ->get(route('admin.admin-income.export', ['entry_date' => '2026-03-31']))
            ->assertOk();

        Excel::assertDownloaded('admin-income-register-export.xlsx', function ($export) use ($attachment, $entry) {
            $this->assertInstanceOf(WorkbookExport::class, $export);
            $entries = $export->sheets()[1]->array();
            $this->assertSame('Attachment Names', $entries[0][6]);
            $this->assertSame('Attachment Download URLs', $entries[0][7]);
            $this->assertSame('admin-income-proof.pdf', $entries[1][6]);
            $this->assertSame(
                route('admin.admin-income.attachments.download', ['adminIncome' => $entry, 'attachment' => $attachment]),
                $entries[1][7]
            );

            return true;
        });
    }

    public function test_admin_can_open_admin_income_date_print_view_with_attachment_links(): void
    {
        $admin = User::factory()->admin()->create();
        $entry = AdminIncomeEntry::factory()->create([
            'user_id' => $admin->id,
            'entry_date' => '2026-03-31',
            'name' => 'Head office transfer',
            'amount_minor' => 22000,
        ]);

        $attachment = $entry->attachments()->create([
            'uploaded_by' => $admin->id,
            'disk' => 'local',
            'storage_path' => 'attachments/admin-income-print.pdf',
            'original_name' => 'admin-income-print.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.admin-income.print-date', ['entryDate' => '2026-03-31']))
            ->assertOk()
            ->assertSee('admin-income-print.pdf')
            ->assertSee(route('admin.admin-income.attachments.download', ['adminIncome' => $entry, 'attachment' => $attachment]), false);
    }

    public function test_employee_cannot_access_admin_income_routes(): void
    {
        $employee = User::factory()->employeeA()->create();

        $this->actingAs($employee)
            ->get(route('admin.admin-income.index'))
            ->assertForbidden();
    }
}
