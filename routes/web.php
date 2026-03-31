<?php

use App\Http\Controllers\Access\DashboardRedirectController;
use App\Http\Controllers\Access\EmployeeDashboardController;
use App\Http\Controllers\Access\VenueSelectionController;
use App\Http\Controllers\Admin\Ledgers\AdminIncomeEntryController;
use App\Http\Controllers\Admin\MasterData\EmployeeAssignmentController;
use App\Http\Controllers\Admin\MasterData\EmployeeController;
use App\Http\Controllers\Admin\MasterData\PackageController;
use App\Http\Controllers\Admin\Reports\AdminIncomeReportController;
use App\Http\Controllers\Admin\Reports\DailyBillingReportController;
use App\Http\Controllers\Admin\Reports\DailyIncomeReportController;
use App\Http\Controllers\Admin\Reports\DashboardController as AdminReportsDashboardController;
use App\Http\Controllers\Admin\Reports\FunctionEntryReportController;
use App\Http\Controllers\Admin\Reports\ReportIndexController;
use App\Http\Controllers\Admin\MasterData\ServiceController;
use App\Http\Controllers\Admin\MasterData\VenueController;
use App\Http\Controllers\Admin\Reports\VendorEntryReportController;
use App\Http\Controllers\Employee\Ledgers\DailyBillingEntryController;
use App\Http\Controllers\Employee\Ledgers\DailyIncomeEntryController;
use App\Http\Controllers\Employee\Ledgers\VendorEntryController;
use App\Http\Controllers\Employee\Functions\FunctionAttachmentController;
use App\Http\Controllers\Employee\Functions\FunctionDiscountController;
use App\Http\Controllers\Employee\Functions\FunctionEntryController;
use App\Http\Controllers\Employee\Functions\FunctionExtraChargeController;
use App\Http\Controllers\Employee\Functions\FunctionInstallmentController;
use App\Http\Controllers\Employee\Functions\FunctionPackageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminReportsDashboardController::class)->name('dashboard');
        Route::resource('admin-income', AdminIncomeEntryController::class)
            ->parameters(['admin-income' => 'adminIncome']);
        Route::get('/admin-income/{adminIncome}/attachments/{attachment}/preview', [AdminIncomeEntryController::class, 'preview'])
            ->name('admin-income.attachments.preview');
        Route::get('/admin-income/{adminIncome}/attachments/{attachment}', [AdminIncomeEntryController::class, 'download'])
            ->name('admin-income.attachments.download');
        Route::delete('/admin-income/{adminIncome}/attachments/{attachment}', [AdminIncomeEntryController::class, 'destroyAttachment'])
            ->name('admin-income.attachments.destroy');

        Route::prefix('reports')
            ->name('reports.')
            ->group(function () {
                Route::get('/', ReportIndexController::class)->name('index');
                Route::get('/functions', [FunctionEntryReportController::class, 'index'])->name('functions.index');
                Route::get('/functions/export', [FunctionEntryReportController::class, 'export'])->name('functions.export');
                Route::get('/daily-income', [DailyIncomeReportController::class, 'index'])->name('daily-income.index');
                Route::get('/daily-income/export', [DailyIncomeReportController::class, 'export'])->name('daily-income.export');
                Route::get('/daily-billing', [DailyBillingReportController::class, 'index'])->name('daily-billing.index');
                Route::get('/daily-billing/export', [DailyBillingReportController::class, 'export'])->name('daily-billing.export');
                Route::get('/vendor-entries', [VendorEntryReportController::class, 'index'])->name('vendor-entries.index');
                Route::get('/vendor-entries/export', [VendorEntryReportController::class, 'export'])->name('vendor-entries.export');
                Route::get('/admin-income', [AdminIncomeReportController::class, 'index'])->name('admin-income.index');
                Route::get('/admin-income/export', [AdminIncomeReportController::class, 'export'])->name('admin-income.export');
            });

        Route::prefix('master-data')
            ->name('master-data.')
            ->group(function () {
                Route::resource('venues', VenueController::class)->except('show');
                Route::resource('employees', EmployeeController::class)
                    ->parameters(['employees' => 'employee'])
                    ->except(['show', 'destroy']);
                Route::get('/employees/{employee}/assignments', [EmployeeAssignmentController::class, 'edit'])
                    ->name('employees.assignments.edit');
                Route::put('/employees/{employee}/assignments', [EmployeeAssignmentController::class, 'update'])
                    ->name('employees.assignments.update');
                Route::resource('services', ServiceController::class)->except('show');
                Route::resource('packages', PackageController::class)->except('show');
            });
    });

Route::middleware(['auth', 'role:employee'])
    ->prefix('venues')
    ->name('venues.')
    ->group(function () {
        Route::get('/select', [VenueSelectionController::class, 'index'])->name('select');
        Route::post('/select', [VenueSelectionController::class, 'store'])->name('store');
        Route::post('/switch', [VenueSelectionController::class, 'update'])->name('switch');
    });

Route::middleware(['auth', 'role:employee', 'venue.selected'])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {
        Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');

        Route::middleware('role:employee_a,employee_b')->group(function () {
            Route::resource('daily-income', DailyIncomeEntryController::class)
                ->parameters(['daily-income' => 'dailyIncome']);
            Route::get('/daily-income/{dailyIncome}/attachments/{attachment}/preview', [DailyIncomeEntryController::class, 'preview'])
                ->name('daily-income.attachments.preview');
            Route::get('/daily-income/{dailyIncome}/attachments/{attachment}', [DailyIncomeEntryController::class, 'download'])
                ->name('daily-income.attachments.download');
            Route::delete('/daily-income/{dailyIncome}/attachments/{attachment}', [DailyIncomeEntryController::class, 'destroyAttachment'])
                ->name('daily-income.attachments.destroy');

            Route::resource('daily-billing', DailyBillingEntryController::class)
                ->parameters(['daily-billing' => 'dailyBilling']);
            Route::get('/daily-billing/{dailyBilling}/attachments/{attachment}/preview', [DailyBillingEntryController::class, 'preview'])
                ->name('daily-billing.attachments.preview');
            Route::get('/daily-billing/{dailyBilling}/attachments/{attachment}', [DailyBillingEntryController::class, 'download'])
                ->name('daily-billing.attachments.download');
            Route::delete('/daily-billing/{dailyBilling}/attachments/{attachment}', [DailyBillingEntryController::class, 'destroyAttachment'])
                ->name('daily-billing.attachments.destroy');
        });

        Route::middleware('role:employee_b')->group(function () {
            Route::resource('vendor-entries', VendorEntryController::class)
                ->parameters(['vendor-entries' => 'vendorEntry']);
            Route::put('/vendor-entries/vendors/{venueVendor}', [VendorEntryController::class, 'updateVendorName'])
                ->name('vendor-entries.vendors.update');
            Route::get('/vendor-entries/{vendorEntry}/attachments/{attachment}/preview', [VendorEntryController::class, 'preview'])
                ->name('vendor-entries.attachments.preview');
            Route::get('/vendor-entries/{vendorEntry}/attachments/{attachment}', [VendorEntryController::class, 'download'])
                ->name('vendor-entries.attachments.download');
            Route::delete('/vendor-entries/{vendorEntry}/attachments/{attachment}', [VendorEntryController::class, 'destroyAttachment'])
                ->name('vendor-entries.attachments.destroy');
        });

        Route::resource('functions', FunctionEntryController::class)
            ->parameters(['functions' => 'functionEntry']);
        Route::post('/functions/{functionEntry}/packages', [FunctionPackageController::class, 'store'])
            ->name('functions.packages.store');
        Route::put('/functions/{functionEntry}/packages/{functionPackage}', [FunctionPackageController::class, 'update'])
            ->name('functions.packages.update');
        Route::delete('/functions/{functionEntry}/packages/{functionPackage}', [FunctionPackageController::class, 'destroy'])
            ->name('functions.packages.destroy');
        Route::post('/functions/{functionEntry}/extra-charges', [FunctionExtraChargeController::class, 'store'])
            ->name('functions.extra-charges.store');
        Route::put('/functions/{functionEntry}/extra-charges/{functionExtraCharge}', [FunctionExtraChargeController::class, 'update'])
            ->name('functions.extra-charges.update');
        Route::delete('/functions/{functionEntry}/extra-charges/{functionExtraCharge}', [FunctionExtraChargeController::class, 'destroy'])
            ->name('functions.extra-charges.destroy');
        Route::post('/functions/{functionEntry}/installments', [FunctionInstallmentController::class, 'store'])
            ->name('functions.installments.store');
        Route::put('/functions/{functionEntry}/installments/{functionInstallment}', [FunctionInstallmentController::class, 'update'])
            ->name('functions.installments.update');
        Route::delete('/functions/{functionEntry}/installments/{functionInstallment}', [FunctionInstallmentController::class, 'destroy'])
            ->name('functions.installments.destroy');
        Route::post('/functions/{functionEntry}/discounts', [FunctionDiscountController::class, 'store'])
            ->name('functions.discounts.store');
        Route::put('/functions/{functionEntry}/discounts/{functionDiscount}', [FunctionDiscountController::class, 'update'])
            ->name('functions.discounts.update');
        Route::delete('/functions/{functionEntry}/discounts/{functionDiscount}', [FunctionDiscountController::class, 'destroy'])
            ->name('functions.discounts.destroy');
        Route::get('/functions/{functionEntry}/attachments/{attachment}/preview', [FunctionAttachmentController::class, 'preview'])
            ->name('functions.attachments.preview');
        Route::get('/functions/{functionEntry}/attachments/{attachment}', [FunctionAttachmentController::class, 'download'])
            ->name('functions.attachments.download');
        Route::delete('/functions/{functionEntry}/attachments/{attachment}', [FunctionAttachmentController::class, 'destroy'])
            ->name('functions.attachments.destroy');
    });

require __DIR__.'/auth.php';
