<?php

use App\Http\Controllers\Admin\AvailabilityPeriodController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UnavailablePeriodController;
use Illuminate\Support\Facades\Route;

// Admin routes with authentication and verification middleware
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Availability Periods
    Route::resource('availability-periods', AvailabilityPeriodController::class);
    Route::patch('availability-periods/{availabilityPeriod}/toggle', [AvailabilityPeriodController::class, 'toggle'])
        ->name('availability-periods.toggle');

    // Bookings
    Route::resource('bookings', BookingController::class);
    Route::patch('bookings/{booking}/confirm', [BookingController::class, 'confirm'])
        ->name('bookings.confirm');
    Route::patch('bookings/{booking}/reject', [BookingController::class, 'reject'])
        ->name('bookings.reject');
    Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->name('bookings.cancel');

    // Unavailable Periods
    Route::resource('unavailable-periods', UnavailablePeriodController::class);

});
