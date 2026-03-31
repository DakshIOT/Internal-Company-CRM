<?php

namespace App\Services\Reports;

use App\Reports\Filters\ReportFilters;
use App\Support\Reports\ReportModule;

class AdminDashboardMetricsService
{
    public function __construct(
        protected FunctionEntryReportQuery $functionReportQuery,
        protected DailyIncomeReportQuery $dailyIncomeReportQuery,
        protected DailyBillingReportQuery $dailyBillingReportQuery,
        protected VendorEntryReportQuery $vendorEntryReportQuery,
        protected AdminIncomeReportQuery $adminIncomeReportQuery
    ) {
    }

    public function overview(ReportFilters $filters): array
    {
        $function = $this->functionReportQuery->summary($filters);
        $dailyIncome = $this->dailyIncomeReportQuery->summary($filters);
        $dailyBilling = $this->dailyBillingReportQuery->summary($filters);
        $vendorEntry = $this->vendorEntryReportQuery->summary($filters);
        $adminIncome = $this->adminIncomeReportQuery->summary($filters);

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
}
