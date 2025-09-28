<?php

use App\Http\Controllers\Admin\AvailabilityController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RestrictionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Availabilities
    Route::get('availabilities', [AvailabilityController::class, 'index'])->name('availabilities.index');
    Route::put('availabilities', [AvailabilityController::class, 'update'])->name('availabilities.update');
    Route::post('availabilities/toggle-day', [AvailabilityController::class, 'toggleDay'])->name('availabilities.toggle-day');

    // Restrictions
    Route::resource('restrictions', RestrictionController::class);

    // Bookings
    Route::resource('bookings', BookingController::class)->only(['index', 'show', 'destroy']);
    Route::patch('bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::patch('bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
    Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::patch('bookings/bulk-action', [BookingController::class, 'bulkAction'])->name('bookings.bulk-action');
    Route::get('bookings/export', [BookingController::class, 'export'])->name('bookings.export');
});
