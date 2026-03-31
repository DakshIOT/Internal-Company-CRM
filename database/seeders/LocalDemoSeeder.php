<?php

namespace Database\Seeders;

use App\Models\AdminIncomeEntry;
use App\Models\Attachment;
use App\Models\DailyBillingEntry;
use App\Models\DailyIncomeEntry;
use App\Models\FunctionDiscount;
use App\Models\FunctionEntry;
use App\Models\FunctionExtraCharge;
use App\Models\FunctionInstallment;
use App\Models\FunctionPackage;
use App\Models\FunctionServiceLine;
use App\Models\Package;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorEntry;
use App\Models\Venue;
use App\Services\Functions\FunctionEntryTotalsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Password@123');

        $users = $this->seedUsers($password);
        $venues = $this->seedVenues();
        $services = $this->seedServices();
        $packages = $this->seedPackages($services);

        $this->syncAssignments($users, $venues, $services, $packages);
        $this->clearTransactionalData();
        $this->seedFunctionEntries($users, $venues, $packages);
        $this->seedLedgerEntries($users, $venues);
    }

    protected function seedUsers(string $password): array
    {
        return [
            'admin' => User::query()->updateOrCreate(
                ['email' => 'admin@interiorcrm.local'],
                ['name' => 'CRM Admin', 'role' => 'admin', 'is_active' => true, 'password' => $password, 'email_verified_at' => now()]
            ),
            'employee_a' => User::query()->updateOrCreate(
                ['email' => 'employee.a@interiorcrm.local'],
                ['name' => 'Employee A', 'role' => 'employee_a', 'is_active' => true, 'password' => $password, 'email_verified_at' => now()]
            ),
            'employee_b' => User::query()->updateOrCreate(
                ['email' => 'employee.b@interiorcrm.local'],
                ['name' => 'Employee B', 'role' => 'employee_b', 'is_active' => true, 'password' => $password, 'email_verified_at' => now()]
            ),
            'employee_c' => User::query()->updateOrCreate(
                ['email' => 'employee.c@interiorcrm.local'],
                ['name' => 'Employee C', 'role' => 'employee_c', 'is_active' => true, 'password' => $password, 'email_verified_at' => now()]
            ),
        ];
    }

    protected function seedVenues(): array
    {
        $venues = [
            'SKY-HALL' => ['name' => 'Sky Hall', 'vendors' => ['Lights', 'Sound', 'Catering', 'Flowers']],
            'GARDEN-COURT' => ['name' => 'Garden Court', 'vendors' => ['Stage', 'Media', 'Food', 'Decor']],
            'LAKE-VIEW' => ['name' => 'Lake View', 'vendors' => ['Audio', 'Setup', 'Dining', 'Styling']],
        ];

        $result = [];

        foreach ($venues as $code => $data) {
            $venue = Venue::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $data['name'], 'is_active' => true]
            );

            $venue->syncVendorSlots([
                1 => $data['vendors'][0],
                2 => $data['vendors'][1],
                3 => $data['vendors'][2],
                4 => $data['vendors'][3],
            ]);

            $result[$code] = $venue->fresh('vendors');
        }

        return $result;
    }

    protected function seedServices(array $services = []): array
    {
        $definitions = [
            'PHOTO' => ['name' => 'Photography', 'standard_rate_minor' => 25000, 'notes' => 'Premium camera coverage'],
            'CATER' => ['name' => 'Catering', 'standard_rate_minor' => 32000, 'notes' => 'Food and beverages'],
            'DECOR' => ['name' => 'Decor', 'standard_rate_minor' => 18000, 'notes' => 'Stage and venue styling'],
            'SOUND' => ['name' => 'Sound Setup', 'standard_rate_minor' => 14000, 'notes' => 'Audio support and setup'],
        ];

        $result = [];

        foreach ($definitions as $code => $data) {
            $result[$code] = Service::query()->updateOrCreate(
                ['code' => $code],
                array_merge($data, ['is_active' => true])
            );
        }

        return $result;
    }

    protected function seedPackages(array $services): array
    {
        $packages = [
            'WED-PRIME' => [
                'name' => 'Wedding Prime',
                'description' => 'Large celebration package',
                'services' => ['PHOTO', 'CATER', 'DECOR', 'SOUND'],
            ],
            'WED-SIGN' => [
                'name' => 'Wedding Signature',
                'description' => 'Balanced wedding package',
                'services' => ['PHOTO', 'DECOR', 'SOUND'],
            ],
            'CORP-LITE' => [
                'name' => 'Corporate Lite',
                'description' => 'Lean corporate package',
                'services' => ['SOUND', 'DECOR'],
            ],
        ];

        $result = [];

        foreach ($packages as $code => $data) {
            $package = Package::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $data['name'], 'description' => $data['description'], 'is_active' => true]
            );

            $package->services()->sync(
                collect($data['services'])->mapWithKeys(fn (string $serviceCode, int $index) => [
                    $services[$serviceCode]->id => ['sort_order' => $index + 1],
                ])->all()
            );

            $result[$code] = $package->fresh('services');
        }

        return $result;
    }

    protected function syncAssignments(array $users, array $venues, array $services, array $packages): void
    {
        $users['employee_a']->venues()->sync([
            $venues['SKY-HALL']->id => ['frozen_fund_minor' => 15000],
            $venues['GARDEN-COURT']->id => ['frozen_fund_minor' => 12000],
        ]);

        $users['employee_b']->venues()->sync([
            $venues['SKY-HALL']->id => ['frozen_fund_minor' => 0],
            $venues['GARDEN-COURT']->id => ['frozen_fund_minor' => 0],
            $venues['LAKE-VIEW']->id => ['frozen_fund_minor' => 0],
        ]);

        $users['employee_c']->venues()->sync([
            $venues['SKY-HALL']->id => ['frozen_fund_minor' => 0],
            $venues['LAKE-VIEW']->id => ['frozen_fund_minor' => 0],
        ]);

        foreach (['employee_a', 'employee_b', 'employee_c'] as $employeeKey) {
            $users[$employeeKey]->serviceAssignments()->delete();
            $users[$employeeKey]->packageAssignments()->delete();
        }

        $this->syncServicesForUser($users['employee_a'], [
            $venues['SKY-HALL']->id => [$services['PHOTO']->id, $services['CATER']->id, $services['DECOR']->id, $services['SOUND']->id],
            $venues['GARDEN-COURT']->id => [$services['PHOTO']->id, $services['DECOR']->id, $services['SOUND']->id],
        ]);
        $this->syncPackagesForUser($users['employee_a'], [
            $venues['SKY-HALL']->id => [$packages['WED-PRIME']->id, $packages['WED-SIGN']->id],
            $venues['GARDEN-COURT']->id => [$packages['WED-SIGN']->id, $packages['CORP-LITE']->id],
        ]);

        $this->syncServicesForUser($users['employee_b'], [
            $venues['SKY-HALL']->id => [$services['PHOTO']->id, $services['DECOR']->id, $services['SOUND']->id],
            $venues['GARDEN-COURT']->id => [$services['PHOTO']->id, $services['CATER']->id, $services['DECOR']->id, $services['SOUND']->id],
            $venues['LAKE-VIEW']->id => [$services['DECOR']->id, $services['SOUND']->id],
        ]);
        $this->syncPackagesForUser($users['employee_b'], [
            $venues['SKY-HALL']->id => [$packages['WED-SIGN']->id],
            $venues['GARDEN-COURT']->id => [$packages['WED-PRIME']->id, $packages['CORP-LITE']->id],
            $venues['LAKE-VIEW']->id => [$packages['CORP-LITE']->id],
        ]);

        $this->syncServicesForUser($users['employee_c'], [
            $venues['SKY-HALL']->id => [$services['PHOTO']->id, $services['DECOR']->id, $services['SOUND']->id],
            $venues['LAKE-VIEW']->id => [$services['PHOTO']->id, $services['DECOR']->id],
        ]);
        $this->syncPackagesForUser($users['employee_c'], [
            $venues['SKY-HALL']->id => [$packages['WED-SIGN']->id],
            $venues['LAKE-VIEW']->id => [$packages['WED-PRIME']->id],
        ]);
    }

    protected function syncServicesForUser(User $user, array $map): void
    {
        foreach ($map as $venueId => $serviceIds) {
            $user->serviceAssignments()->createMany(
                collect($serviceIds)->map(fn (int $serviceId) => [
                    'venue_id' => $venueId,
                    'service_id' => $serviceId,
                ])->all()
            );
        }
    }

    protected function syncPackagesForUser(User $user, array $map): void
    {
        foreach ($map as $venueId => $packageIds) {
            $user->packageAssignments()->createMany(
                collect($packageIds)->map(fn (int $packageId) => [
                    'venue_id' => $venueId,
                    'package_id' => $packageId,
                ])->all()
            );
        }
    }

    protected function clearTransactionalData(): void
    {
        Attachment::query()->delete();
        FunctionServiceLine::query()->delete();
        FunctionPackage::query()->delete();
        FunctionExtraCharge::query()->delete();
        FunctionInstallment::query()->delete();
        FunctionDiscount::query()->delete();
        FunctionEntry::query()->delete();
        DailyIncomeEntry::query()->delete();
        DailyBillingEntry::query()->delete();
        VendorEntry::query()->delete();
        AdminIncomeEntry::query()->delete();
    }

    protected function seedFunctionEntries(array $users, array $venues, array $packages): void
    {
        $specs = [
            ['user' => 'employee_a', 'venue' => 'SKY-HALL', 'package' => 'WED-PRIME', 'date' => '2026-03-04', 'name' => 'Sky Wedding Morning', 'notes' => 'Premium hall booking', 'extra' => 4000, 'discount' => 2500, 'installments' => [22000, 15000]],
            ['user' => 'employee_a', 'venue' => 'SKY-HALL', 'package' => 'WED-SIGN', 'date' => '2026-03-04', 'name' => 'Sky Reception', 'notes' => 'Evening setup', 'extra' => 2500, 'discount' => 0, 'installments' => [18000]],
            ['user' => 'employee_a', 'venue' => 'GARDEN-COURT', 'package' => 'WED-SIGN', 'date' => '2026-03-08', 'name' => 'Garden Engagement', 'notes' => 'Outdoor walkthrough', 'extra' => 2000, 'discount' => 1500, 'installments' => [14000, 12000]],
            ['user' => 'employee_a', 'venue' => 'GARDEN-COURT', 'package' => 'CORP-LITE', 'date' => '2026-03-11', 'name' => 'Garden Corporate Dinner', 'notes' => 'Corporate client event', 'extra' => 3000, 'discount' => 1000, 'installments' => [15000]],
            ['user' => 'employee_b', 'venue' => 'SKY-HALL', 'package' => 'WED-SIGN', 'date' => '2026-03-07', 'name' => 'Sky Sangeet', 'notes' => 'Employee B managed event', 'extra' => 1800, 'discount' => 0, 'installments' => [13000, 9000]],
            ['user' => 'employee_b', 'venue' => 'GARDEN-COURT', 'package' => 'WED-PRIME', 'date' => '2026-03-10', 'name' => 'Garden Wedding Night', 'notes' => 'Full package event', 'extra' => 5000, 'discount' => 3000, 'installments' => [26000, 14000]],
            ['user' => 'employee_b', 'venue' => 'LAKE-VIEW', 'package' => 'CORP-LITE', 'date' => '2026-03-12', 'name' => 'Lake Product Launch', 'notes' => 'Vendor heavy event', 'extra' => 2200, 'discount' => 500, 'installments' => [16000]],
            ['user' => 'employee_b', 'venue' => 'LAKE-VIEW', 'package' => 'CORP-LITE', 'date' => '2026-03-12', 'name' => 'Lake Team Meet', 'notes' => 'Short event', 'extra' => 1200, 'discount' => 0, 'installments' => [9000]],
            ['user' => 'employee_c', 'venue' => 'SKY-HALL', 'package' => 'WED-SIGN', 'date' => '2026-03-15', 'name' => 'Sky Decor Walkthrough', 'notes' => 'Function only user', 'extra' => 0, 'discount' => 800, 'installments' => [9000]],
            ['user' => 'employee_c', 'venue' => 'LAKE-VIEW', 'package' => 'WED-PRIME', 'date' => '2026-03-15', 'name' => 'Lake Bridal Preview', 'notes' => 'High-value preview event', 'extra' => 3500, 'discount' => 1200, 'installments' => [12000, 11000]],
        ];

        foreach ($specs as $index => $spec) {
            $this->createFunctionEntry(
                $users[$spec['user']],
                $venues[$spec['venue']],
                $packages[$spec['package']],
                $spec,
                $index + 1
            );
        }
    }

    protected function createFunctionEntry(User $user, Venue $venue, Package $package, array $spec, int $seedIndex): void
    {
        $entry = FunctionEntry::query()->create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'entry_date' => $spec['date'],
            'name' => $spec['name'],
            'notes' => $spec['notes'],
            'package_total_minor' => 0,
            'extra_charge_total_minor' => 0,
            'discount_total_minor' => 0,
            'function_total_minor' => 0,
            'paid_total_minor' => 0,
            'pending_total_minor' => 0,
            'frozen_fund_minor' => 0,
            'net_total_after_frozen_fund_minor' => 0,
        ]);

        $functionPackage = FunctionPackage::query()->create([
            'function_entry_id' => $entry->id,
            'package_id' => $package->id,
            'name_snapshot' => $package->name,
            'code_snapshot' => $package->code,
            'total_minor' => 0,
        ]);

        $package->loadMissing('services');

        foreach ($package->services->sortBy('pivot.sort_order')->values() as $serviceIndex => $service) {
            $persons = (($seedIndex + $serviceIndex) % 2) + 1;
            $lineExtraMinor = $serviceIndex === 0 ? 1000 + ($seedIndex * 100) : ($serviceIndex === 1 ? 600 : 0);

            FunctionServiceLine::query()->create([
                'function_package_id' => $functionPackage->id,
                'service_id' => $service->id,
                'sort_order' => $serviceIndex + 1,
                'is_selected' => true,
                'item_name_snapshot' => $service->name,
                'rate_minor' => $service->standard_rate_minor,
                'persons' => $persons,
                'extra_charge_minor' => $lineExtraMinor,
                'notes' => 'Seeded service row',
                'line_total_minor' => ($persons * $service->standard_rate_minor) + $lineExtraMinor,
            ]);
        }

        if ((int) $spec['extra'] > 0) {
            FunctionExtraCharge::query()->create([
                'function_entry_id' => $entry->id,
                'entry_date' => $spec['date'],
                'name' => 'Extra support',
                'mode' => 'cash',
                'amount_minor' => (int) $spec['extra'],
                'note' => 'Seeded extra charge',
            ]);
        }

        if ((int) $spec['discount'] > 0) {
            FunctionDiscount::query()->create([
                'function_entry_id' => $entry->id,
                'entry_date' => $spec['date'],
                'name' => 'Approved discount',
                'mode' => 'bank',
                'amount_minor' => (int) $spec['discount'],
                'note' => 'Seeded discount',
            ]);
        }

        foreach ($spec['installments'] as $installmentIndex => $amountMinor) {
            FunctionInstallment::query()->create([
                'function_entry_id' => $entry->id,
                'entry_date' => $spec['date'],
                'name' => 'Installment '.($installmentIndex + 1),
                'mode' => $installmentIndex % 2 === 0 ? 'upi' : 'bank',
                'amount_minor' => $amountMinor,
                'note' => 'Seeded installment',
            ]);
        }

        app(FunctionEntryTotalsService::class)->recalculate($entry->fresh());
    }

    protected function seedLedgerEntries(array $users, array $venues): void
    {
        $this->seedAmountEntries(DailyIncomeEntry::class, [
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['SKY-HALL']->id, 'entry_date' => '2026-03-04', 'name' => 'Sky advance', 'amount_minor' => 12000, 'notes' => 'Seeded income'],
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['SKY-HALL']->id, 'entry_date' => '2026-03-04', 'name' => 'Sky balance', 'amount_minor' => 16000, 'notes' => 'Seeded income'],
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'entry_date' => '2026-03-08', 'name' => 'Garden advance', 'amount_minor' => 18000, 'notes' => 'Seeded income'],
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'entry_date' => '2026-03-11', 'name' => 'Garden balance', 'amount_minor' => 14000, 'notes' => 'Seeded income'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['SKY-HALL']->id, 'entry_date' => '2026-03-07', 'name' => 'Sky festival payment', 'amount_minor' => 10000, 'notes' => 'Seeded income'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'entry_date' => '2026-03-10', 'name' => 'Garden event payment', 'amount_minor' => 22000, 'notes' => 'Seeded income'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['LAKE-VIEW']->id, 'entry_date' => '2026-03-12', 'name' => 'Lake launch payment', 'amount_minor' => 17000, 'notes' => 'Seeded income'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['LAKE-VIEW']->id, 'entry_date' => '2026-03-12', 'name' => 'Lake meeting payment', 'amount_minor' => 9000, 'notes' => 'Seeded income'],
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['SKY-HALL']->id, 'entry_date' => '2026-03-14', 'name' => 'Sky manual receipt', 'amount_minor' => 8000, 'notes' => 'Seeded income'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'entry_date' => '2026-03-15', 'name' => 'Garden manual receipt', 'amount_minor' => 11000, 'notes' => 'Seeded income'],
        ]);

        $this->seedAmountEntries(DailyBillingEntry::class, [
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['SKY-HALL']->id, 'entry_date' => '2026-03-04', 'name' => 'Sky utilities', 'amount_minor' => 6000, 'notes' => 'Seeded billing'],
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['SKY-HALL']->id, 'entry_date' => '2026-03-04', 'name' => 'Sky setup', 'amount_minor' => 4500, 'notes' => 'Seeded billing'],
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'entry_date' => '2026-03-08', 'name' => 'Garden materials', 'amount_minor' => 5200, 'notes' => 'Seeded billing'],
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'entry_date' => '2026-03-11', 'name' => 'Garden labour', 'amount_minor' => 7100, 'notes' => 'Seeded billing'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['SKY-HALL']->id, 'entry_date' => '2026-03-07', 'name' => 'Sky equipment', 'amount_minor' => 3800, 'notes' => 'Seeded billing'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'entry_date' => '2026-03-10', 'name' => 'Garden transport', 'amount_minor' => 6400, 'notes' => 'Seeded billing'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['LAKE-VIEW']->id, 'entry_date' => '2026-03-12', 'name' => 'Lake equipment', 'amount_minor' => 5600, 'notes' => 'Seeded billing'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['LAKE-VIEW']->id, 'entry_date' => '2026-03-12', 'name' => 'Lake staging', 'amount_minor' => 4300, 'notes' => 'Seeded billing'],
            ['user_id' => $users['employee_a']->id, 'venue_id' => $venues['SKY-HALL']->id, 'entry_date' => '2026-03-14', 'name' => 'Sky emergency supply', 'amount_minor' => 2900, 'notes' => 'Seeded billing'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'entry_date' => '2026-03-15', 'name' => 'Garden support team', 'amount_minor' => 4800, 'notes' => 'Seeded billing'],
        ]);

        $lakeAudio = $venues['LAKE-VIEW']->vendors->firstWhere('slot_number', 1);
        $lakeSetup = $venues['LAKE-VIEW']->vendors->firstWhere('slot_number', 2);
        $gardenMedia = $venues['GARDEN-COURT']->vendors->firstWhere('slot_number', 2);
        $gardenDecor = $venues['GARDEN-COURT']->vendors->firstWhere('slot_number', 4);
        $skyLights = $venues['SKY-HALL']->vendors->firstWhere('slot_number', 1);
        $skySound = $venues['SKY-HALL']->vendors->firstWhere('slot_number', 2);

        $this->seedAmountEntries(VendorEntry::class, [
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['SKY-HALL']->id, 'venue_vendor_id' => $skyLights->id, 'vendor_name_snapshot' => $skyLights->name, 'entry_date' => '2026-03-07', 'name' => 'Light advance', 'amount_minor' => 9000, 'notes' => 'Seeded vendor'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['SKY-HALL']->id, 'venue_vendor_id' => $skySound->id, 'vendor_name_snapshot' => $skySound->name, 'entry_date' => '2026-03-07', 'name' => 'Sound balance', 'amount_minor' => 7600, 'notes' => 'Seeded vendor'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'venue_vendor_id' => $gardenMedia->id, 'vendor_name_snapshot' => $gardenMedia->name, 'entry_date' => '2026-03-10', 'name' => 'Media booking', 'amount_minor' => 11200, 'notes' => 'Seeded vendor'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'venue_vendor_id' => $gardenDecor->id, 'vendor_name_snapshot' => $gardenDecor->name, 'entry_date' => '2026-03-10', 'name' => 'Decor material', 'amount_minor' => 8400, 'notes' => 'Seeded vendor'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['LAKE-VIEW']->id, 'venue_vendor_id' => $lakeAudio->id, 'vendor_name_snapshot' => $lakeAudio->name, 'entry_date' => '2026-03-12', 'name' => 'Audio advance', 'amount_minor' => 9600, 'notes' => 'Seeded vendor'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['LAKE-VIEW']->id, 'venue_vendor_id' => $lakeSetup->id, 'vendor_name_snapshot' => $lakeSetup->name, 'entry_date' => '2026-03-12', 'name' => 'Setup charge', 'amount_minor' => 7200, 'notes' => 'Seeded vendor'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['SKY-HALL']->id, 'venue_vendor_id' => $skyLights->id, 'vendor_name_snapshot' => $skyLights->name, 'entry_date' => '2026-03-14', 'name' => 'Light top-up', 'amount_minor' => 4100, 'notes' => 'Seeded vendor'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['GARDEN-COURT']->id, 'venue_vendor_id' => $gardenMedia->id, 'vendor_name_snapshot' => $gardenMedia->name, 'entry_date' => '2026-03-15', 'name' => 'Media revision', 'amount_minor' => 5300, 'notes' => 'Seeded vendor'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['LAKE-VIEW']->id, 'venue_vendor_id' => $lakeAudio->id, 'vendor_name_snapshot' => $lakeAudio->name, 'entry_date' => '2026-03-15', 'name' => 'Audio balance', 'amount_minor' => 6800, 'notes' => 'Seeded vendor'],
            ['user_id' => $users['employee_b']->id, 'venue_id' => $venues['LAKE-VIEW']->id, 'venue_vendor_id' => $lakeSetup->id, 'vendor_name_snapshot' => $lakeSetup->name, 'entry_date' => '2026-03-16', 'name' => 'Setup closeout', 'amount_minor' => 3900, 'notes' => 'Seeded vendor'],
        ]);

        $this->seedAmountEntries(AdminIncomeEntry::class, [
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-01', 'name' => 'Owner collection', 'amount_minor' => 45000, 'notes' => 'Seeded admin income'],
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-03', 'name' => 'Office collection', 'amount_minor' => 32000, 'notes' => 'Seeded admin income'],
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-05', 'name' => 'Advance transfer', 'amount_minor' => 28000, 'notes' => 'Seeded admin income'],
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-07', 'name' => 'Manual collection', 'amount_minor' => 21000, 'notes' => 'Seeded admin income'],
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-10', 'name' => 'Office deposit', 'amount_minor' => 36000, 'notes' => 'Seeded admin income'],
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-12', 'name' => 'Late balance', 'amount_minor' => 19000, 'notes' => 'Seeded admin income'],
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-14', 'name' => 'Client settlement', 'amount_minor' => 26000, 'notes' => 'Seeded admin income'],
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-16', 'name' => 'Desk receipt', 'amount_minor' => 17500, 'notes' => 'Seeded admin income'],
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-18', 'name' => 'Reserve transfer', 'amount_minor' => 40500, 'notes' => 'Seeded admin income'],
            ['user_id' => $users['admin']->id, 'entry_date' => '2026-03-20', 'name' => 'Head office close', 'amount_minor' => 29500, 'notes' => 'Seeded admin income'],
        ]);
    }

    protected function seedAmountEntries(string $modelClass, array $rows): void
    {
        foreach ($rows as $row) {
            $modelClass::query()->create($row);
        }
    }
}
