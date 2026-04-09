<?php

namespace Tests\Feature\Admin\Reports;

use App\Exports\Reports\WorkbookExport;
use App\Models\AdminIncomeEntry;
use App\Models\DailyBillingEntry;
use App\Models\DailyIncomeEntry;
use App\Models\FunctionEntry;
use App\Models\FunctionPackage;
use App\Models\FunctionServiceLine;
use App\Models\Package;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorEntry;
use App\Models\Venue;
use App\Models\VenueVendor;
use App\Support\Role;
use App\Support\Reports\ReportModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_and_function_report_with_employee_scope(): void
    {
        [$admin, $venueA, $venueB, $employeeA] = $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('admin.dashboard', [
                'user_id' => $employeeA->id,
                'venue_id' => $venueA->id,
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertSee('Admin reporting dashboard')
            ->assertSee('Employees');

        $this->actingAs($admin)
            ->get(route('admin.reports.functions.index', [
                'user_id' => $employeeA->id,
                'venue_id' => $venueA->id,
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertSee('Sky Wedding')
            ->assertDontSee('Garden Corporate');
    }

    public function test_dashboard_stays_global_and_reports_prompt_for_employee_before_loading_rows(): void
    {
        [$admin] = $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin reporting dashboard')
            ->assertSee('Open employee-wise reports')
            ->assertDontSee('Select an employee to load report data');

        $this->actingAs($admin)
            ->get(route('admin.reports.functions.index'))
            ->assertOk()
            ->assertSee('Select an employee to load report data')
            ->assertDontSee('Sky Wedding');
    }

    public function test_report_hub_redirects_to_selected_module_only_after_employee_is_selected(): void
    {
        [$admin, $venueA, $venueB, $employeeA] = $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Choose employee and open report');

        $hubResponse = $this->actingAs($admin)
            ->get(route('admin.reports.index', [
                'user_id' => $employeeA->id,
                'module' => ReportModule::DAILY_INCOME,
                'venue_id' => $venueA->id,
            ]));

        $hubResponse
            ->assertRedirectContains(route('admin.reports.daily-income.index', [], false))
            ->assertRedirectContains('user_id='.$employeeA->id)
            ->assertRedirectContains('venue_id='.$venueA->id);
    }

    public function test_non_admin_cannot_access_report_routes(): void
    {
        $employee = User::factory()->employeeA()->create();

        $this->actingAs($employee)
            ->get(route('admin.reports.index'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('admin.reports.functions.index'))
            ->assertForbidden();
    }

    public function test_function_export_download_uses_plain_numeric_workbook_without_currency_markers(): void
    {
        [$admin, $venueA, $venueB, $employeeA] = $this->seedReportData();

        Excel::fake();

        $this->actingAs($admin)
            ->get(route('admin.reports.functions.export', [
                'user_id' => $employeeA->id,
                'venue_id' => $venueA->id,
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]))
            ->assertOk();

        Excel::assertDownloaded('functions-report-2026-03-01-to-2026-03-31.xlsx', function ($export) {
            $this->assertInstanceOf(WorkbookExport::class, $export);

            $sheets = $export->sheets();
            $this->assertCount(4, $sheets);

            $summary = $sheets[0]->array();
            $entries = $sheets[1]->array();
            $serialized = json_encode([$summary, $entries], JSON_THROW_ON_ERROR);

            $this->assertStringNotContainsString('₹', $serialized);
            $this->assertStringNotContainsString('Rs', $serialized);
            $this->assertStringNotContainsString('INR', $serialized);
            $this->assertStringNotContainsString('USD', $serialized);
            $this->assertSame('Function Total', $summary[10][0]);
            $this->assertSame('Entry Date', $entries[0][0]);

            return true;
        });
    }

    public function test_admin_can_export_all_employee_reports_in_one_workbook(): void
    {
        [$admin, $venueA, $venueB, $employeeA] = $this->seedReportData();

        Excel::fake();

        $this->actingAs($admin)
            ->get(route('admin.reports.export-all', [
                'user_id' => $employeeA->id,
                'venue_id' => $venueA->id,
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]))
            ->assertOk();

        Excel::assertDownloaded('employee-'.$employeeA->id.'-all-reports-2026-03-01-to-2026-03-31.xlsx', function ($export) {
            $this->assertInstanceOf(WorkbookExport::class, $export);
            $sheetNames = collect($export->sheets())->map(fn ($sheet) => $sheet->title())->all();

            $this->assertContains('Function Summary', $sheetNames);
            $this->assertContains('Function Entries', $sheetNames);
            $this->assertContains('Daily Income Entries', $sheetNames);
            $this->assertContains('Daily Billing Entries', $sheetNames);
            $this->assertContains('Vendor Entry Entries', $sheetNames);

            return true;
        });
    }

    public function test_dashboard_global_totals_include_all_module_amounts(): void
    {
        [$admin] = $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('790.00')
            ->assertSee('120.00')
            ->assertSee('80.00')
            ->assertSee('70.00')
            ->assertSee('150.00')
            ->assertSee('1,210.00');
    }

    public function test_admin_income_report_ignores_venue_filter_and_remains_global(): void
    {
        [$admin, $venueA] = $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('admin.reports.admin-income.index', ['venue_id' => $venueA->id]))
            ->assertOk()
            ->assertSee('Owner collection')
            ->assertSee('Not applicable');
    }

    private function seedReportData(): array
    {
        $admin = User::factory()->admin()->create();
        $employeeA = User::factory()->employeeA()->create(['name' => 'Employee A']);
        $employeeB = User::factory()->employeeB()->create(['name' => 'Employee B']);

        $venueA = Venue::factory()->create(['name' => 'Sky Hall', 'code' => 'SKY']);
        $venueB = Venue::factory()->create(['name' => 'Garden Court', 'code' => 'GARDEN']);

        $employeeA->venues()->attach($venueA->id, ['frozen_fund_minor' => 15000]);
        $employeeB->venues()->attach($venueB->id, ['frozen_fund_minor' => 0]);

        $package = Package::factory()->create(['name' => 'Wedding Prime']);
        $service = Service::factory()->create(['name' => 'Photography']);
        $package->services()->attach($service->id, ['sort_order' => 1]);

        $functionA = FunctionEntry::factory()->create([
            'user_id' => $employeeA->id,
            'venue_id' => $venueA->id,
            'entry_date' => '2026-03-20',
            'name' => 'Sky Wedding',
            'package_total_minor' => 50000,
            'extra_charge_total_minor' => 5000,
            'discount_total_minor' => 1000,
            'function_total_minor' => 54000,
            'paid_total_minor' => 20000,
            'pending_total_minor' => 34000,
            'frozen_fund_minor' => 15000,
            'net_total_after_frozen_fund_minor' => 39000,
        ]);

        $functionB = FunctionEntry::factory()->create([
            'user_id' => $employeeB->id,
            'venue_id' => $venueB->id,
            'entry_date' => '2026-03-21',
            'name' => 'Garden Corporate',
            'package_total_minor' => 25000,
            'extra_charge_total_minor' => 0,
            'discount_total_minor' => 0,
            'function_total_minor' => 25000,
            'paid_total_minor' => 5000,
            'pending_total_minor' => 20000,
            'frozen_fund_minor' => 0,
            'net_total_after_frozen_fund_minor' => 25000,
        ]);

        $functionPackageA = FunctionPackage::query()->create([
            'function_entry_id' => $functionA->id,
            'package_id' => $package->id,
            'name_snapshot' => $package->name,
            'code_snapshot' => $package->code,
            'total_minor' => 50000,
        ]);

        FunctionServiceLine::query()->create([
            'function_package_id' => $functionPackageA->id,
            'service_id' => $service->id,
            'sort_order' => 1,
            'is_selected' => true,
            'item_name_snapshot' => $service->name,
            'rate_minor' => 50000,
            'persons' => 1,
            'extra_charge_minor' => 0,
            'line_total_minor' => 50000,
        ]);

        $functionPackageB = FunctionPackage::query()->create([
            'function_entry_id' => $functionB->id,
            'package_id' => $package->id,
            'name_snapshot' => $package->name,
            'code_snapshot' => $package->code,
            'total_minor' => 25000,
        ]);

        FunctionServiceLine::query()->create([
            'function_package_id' => $functionPackageB->id,
            'service_id' => $service->id,
            'sort_order' => 1,
            'is_selected' => true,
            'item_name_snapshot' => $service->name,
            'rate_minor' => 25000,
            'persons' => 1,
            'extra_charge_minor' => 0,
            'line_total_minor' => 25000,
        ]);

        DailyIncomeEntry::factory()->create([
            'user_id' => $employeeA->id,
            'venue_id' => $venueA->id,
            'entry_date' => '2026-03-20',
            'name' => 'Income A',
            'amount_minor' => 12000,
        ]);

        DailyBillingEntry::factory()->create([
            'user_id' => $employeeA->id,
            'venue_id' => $venueA->id,
            'entry_date' => '2026-03-20',
            'name' => 'Billing A',
            'amount_minor' => 8000,
        ]);

        $vendor = VenueVendor::factory()->create([
            'venue_id' => $venueA->id,
            'slot_number' => 1,
            'name' => 'Lights',
        ]);

        VendorEntry::factory()->create([
            'user_id' => $employeeB->id,
            'venue_id' => $venueA->id,
            'venue_vendor_id' => $vendor->id,
            'vendor_name_snapshot' => 'Lights',
            'entry_date' => '2026-03-20',
            'name' => 'Vendor A',
            'amount_minor' => 7000,
        ]);

        AdminIncomeEntry::factory()->create([
            'user_id' => $admin->id,
            'entry_date' => '2026-03-20',
            'name' => 'Owner collection',
            'amount_minor' => 15000,
        ]);

        return [$admin, $venueA, $venueB, $employeeA];
    }
}
