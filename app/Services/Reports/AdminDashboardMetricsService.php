<?php

namespace App\Services\Reports;

use App\Models\AdminIncomeEntry;
use App\Models\DailyBillingEntry;
use App\Models\DailyIncomeEntry;
use App\Models\FunctionEntry;
use App\Models\VendorEntry;
use App\Reports\Filters\ReportFilters;
use App\Support\Reports\ReportModule;

class AdminDashboardMetricsService
{
    public function overview(ReportFilters $filters): array
    {
        $function = $this->globalFunctionSummary();
        $dailyIncome = $this->globalAmountSummary(DailyIncomeEntry::class);
        $dailyBilling = $this->globalAmountSummary(DailyBillingEntry::class);
        $vendorEntry = $this->globalAmountSummary(VendorEntry::class);
        $adminIncome = $this->globalAmountSummary(AdminIncomeEntry::class);

        return [
            'primary' => [
                ['label' => 'Function Total', 'value_minor' => $function['function_total_minor'], 'entries' => $function['entry_count']],
                ['label' => 'Paid', 'value_minor' => $function['paid_total_minor'], 'entries' => $function['entry_count']],
                ['label' => 'Pending', 'value_minor' => $function['pending_total_minor'], 'entries' => $function['entry_count']],
                [
                    'label' => 'Overall Recorded Total',
                    'value_minor' => $function['function_total_minor'] + $dailyIncome['amount_minor'] + $dailyBilling['amount_minor'] + $vendorEntry['amount_minor'] + $adminIncome['amount_minor'],
                    'entries' => $function['entry_count'] + $dailyIncome['entry_count'] + $dailyBilling['entry_count'] + $vendorEntry['entry_count'] + $adminIncome['entry_count'],
                ],
            ],
            'secondary' => [
                ['label' => 'Daily Income', 'value_minor' => $dailyIncome['amount_minor'], 'entries' => $dailyIncome['entry_count'], 'module' => ReportModule::DAILY_INCOME],
                ['label' => 'Daily Billing', 'value_minor' => $dailyBilling['amount_minor'], 'entries' => $dailyBilling['entry_count'], 'module' => ReportModule::DAILY_BILLING],
                ['label' => 'Vendor Entry', 'value_minor' => $vendorEntry['amount_minor'], 'entries' => $vendorEntry['entry_count'], 'module' => ReportModule::VENDOR_ENTRIES],
                ['label' => 'Admin Income', 'value_minor' => $adminIncome['amount_minor'], 'entries' => $adminIncome['entry_count'], 'module' => ReportModule::ADMIN_INCOME],
            ],
            'modules' => [
                ['module' => ReportModule::FUNCTIONS, 'label' => ReportModule::label(ReportModule::FUNCTIONS), 'entries' => $function['entry_count'], 'value_minor' => $function['function_total_minor']],
                ['module' => ReportModule::DAILY_INCOME, 'label' => ReportModule::label(ReportModule::DAILY_INCOME), 'entries' => $dailyIncome['entry_count'], 'value_minor' => $dailyIncome['amount_minor']],
                ['module' => ReportModule::DAILY_BILLING, 'label' => ReportModule::label(ReportModule::DAILY_BILLING), 'entries' => $dailyBilling['entry_count'], 'value_minor' => $dailyBilling['amount_minor']],
                ['module' => ReportModule::VENDOR_ENTRIES, 'label' => ReportModule::label(ReportModule::VENDOR_ENTRIES), 'entries' => $vendorEntry['entry_count'], 'value_minor' => $vendorEntry['amount_minor']],
                ['module' => ReportModule::ADMIN_INCOME, 'label' => ReportModule::label(ReportModule::ADMIN_INCOME), 'entries' => $adminIncome['entry_count'], 'value_minor' => $adminIncome['amount_minor']],
            ],
        ];
    }

    private function globalFunctionSummary(): array
    {
        $summary = FunctionEntry::query()
            ->selectRaw('COUNT(*) as entry_count')
            ->selectRaw('COALESCE(SUM(function_total_minor), 0) as function_total_minor')
            ->selectRaw('COALESCE(SUM(paid_total_minor), 0) as paid_total_minor')
            ->selectRaw('COALESCE(SUM(pending_total_minor), 0) as pending_total_minor')
            ->first();

        return [
            'entry_count' => (int) ($summary->entry_count ?? 0),
            'function_total_minor' => (int) ($summary->function_total_minor ?? 0),
            'paid_total_minor' => (int) ($summary->paid_total_minor ?? 0),
            'pending_total_minor' => (int) ($summary->pending_total_minor ?? 0),
        ];
    }

    private function globalAmountSummary(string $modelClass): array
    {
        $summary = $modelClass::query()
            ->selectRaw('COUNT(*) as entry_count')
            ->selectRaw('COALESCE(SUM(amount_minor), 0) as amount_minor')
            ->first();

        return [
            'entry_count' => (int) ($summary->entry_count ?? 0),
            'amount_minor' => (int) ($summary->amount_minor ?? 0),
        ];
    }
}
