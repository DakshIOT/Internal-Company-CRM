<?php

use App\Http\Controllers\Access\AdminDashboardController;
use App\Http\Controllers\Access\DashboardRedirectController;
use App\Http\Controllers\Access\EmployeeDashboardController;
use App\Http\Controllers\Access\VenueSelectionController;
use App\Http\Controllers\Admin\MasterData\EmployeeAssignmentController;
use App\Http\Controllers\Admin\MasterData\EmployeeController;
use App\Http\Controllers\Admin\MasterData\PackageController;
use App\Http\Controllers\Admin\MasterData\ServiceController;
use App\Http\Controllers\Admin\MasterData\VenueController;
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
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

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
