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

});
