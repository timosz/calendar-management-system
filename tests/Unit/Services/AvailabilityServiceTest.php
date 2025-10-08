<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Restriction;
use App\Models\User;
use App\Services\AvailabilityService;

beforeEach(function () {
    $this->service = app(AvailabilityService::class);
    $this->user = User::factory()->create();

    // Setup default config values for tests
    config(['booking.slot_interval_minutes' => 30]);
    config(['booking.slot_duration_minutes' => 60]);
});

test('it excludes confirmed bookings from available slots', function () {
    // Create availability for Friday (day 5)
    Availability::factory()->create([
        'user_id' => $this->user->id,
        'day_of_week' => 5, // Friday
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_active' => true,
    ]);

    // Create a confirmed booking on the same Friday
    $bookingDate = now()->next(Carbon\Carbon::FRIDAY);

    Booking::factory()->create([
        'user_id' => $this->user->id,
        'booking_date' => $bookingDate->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'confirmed',
    ]);

    // Get available slots
    $slots = $this->service->getAvailableSlotsForDate(
        $this->user,
        $bookingDate,
        showUnavailable: false
    );

    expect($slots)->not->toBeEmpty();

    $slotTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $slots);

    // These should be available
    expect($slotTimes)->toContain('09:00-10:00', '11:00-12:00', '12:00-13:00', '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00');

    // These should NOT be available (the booked slot)
    expect($slotTimes)->not->toContain('10:00-11:00', '10:30-11:30');
});

test('it excludes confirmed bookings when using preloaded collections', function () {
    // This test specifically targets the bug where Carbon instances
    // were not being compared correctly with date strings

    // Create availability for Friday (day 5)
    Availability::factory()->create([
        'user_id' => $this->user->id,
        'day_of_week' => 5, // Friday
        'start_time' => '09:00',
        'end_time' => '13:00',
        'is_active' => true,
    ]);

    $bookingDate = now()->next(Carbon\Carbon::FRIDAY);

    Booking::factory()->create([
        'user_id' => $this->user->id,
        'booking_date' => $bookingDate,
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'confirmed',
    ]);

    // Preload data as the optimized method does
    $availabilities = $this->user->availabilities()
        ->active()
        ->get()
        ->keyBy('day_of_week');

    $bookings = $this->user->bookings()
        ->where('booking_date', $bookingDate->toDateString())
        ->confirmed()
        ->get();

    // Get slots with preloaded collections
    $slots = $this->service->getAvailableSlotsForDate(
        $this->user,
        $bookingDate,
        showUnavailable: false,
        availabilities: $availabilities,
        bookings: $bookings
    );

    $slotTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $slots);

    // The 10:00-11:00 slot should NOT be available
    expect($slotTimes)->not->toContain('10:00-11:00', '10:30-11:30');
});

test('it correctly handles multiple bookings on same day', function () {
    Availability::factory()->create([
        'user_id' => $this->user->id,
        'day_of_week' => 5, // Friday
        'start_time' => '09:00',
        'end_time' => '17:00',
        'is_active' => true,
    ]);

    $bookingDate = now()->next(Carbon\Carbon::FRIDAY);

    // Create multiple confirmed bookings
    Booking::factory()->create([
        'user_id' => $this->user->id,
        'booking_date' => $bookingDate,
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'confirmed',
    ]);

    Booking::factory()->create([
        'user_id' => $this->user->id,
        'booking_date' => $bookingDate,
        'start_time' => '13:00:00',
        'end_time' => '14:00:00',
        'status' => 'confirmed',
    ]);

    $slots = $this->service->getAvailableSlotsForDate(
        $this->user,
        $bookingDate,
        showUnavailable: false
    );

    $slotTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $slots);

    // Should exclude both booked slots
    expect($slotTimes)->not->toContain('10:00-11:00', '10:30-11:30', '13:00-14:00', '13:30-14:30');

    // Should include other slots
    expect($slotTimes)->toContain('09:00-10:00', '11:00-12:00', '12:00-13:00', '14:00-15:00', '15:00-16:00', '16:00-17:00');
});

test('it shows unavailable slots when show unavailable is true', function () {
    Availability::factory()->create([
        'user_id' => $this->user->id,
        'day_of_week' => 5, // Friday
        'start_time' => '09:00',
        'end_time' => '17:00',
        'is_active' => true,
    ]);

    $bookingDate = now()->next(Carbon\Carbon::FRIDAY);
    Booking::factory()->create([
        'user_id' => $this->user->id,
        'booking_date' => $bookingDate,
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'confirmed',
    ]);

    // Get slots with showUnavailable = true
    $slots = $this->service->getAvailableSlotsForDate(
        $this->user,
        $bookingDate,
        showUnavailable: true
    );

    // Find the booked slot
    $bookedSlot = collect($slots)->first(function ($slot) {
        return $slot['start_time'] === '10:00' && $slot['end_time'] === '11:00';
    });

    expect($bookedSlot)->not->toBeNull();
    expect($bookedSlot['available'])->toBeFalse();
});

test('it ignores pending bookings when checking availability', function () {
    Availability::factory()->create([
        'user_id' => $this->user->id,
        'day_of_week' => 5, // Friday
        'start_time' => '09:00',
        'end_time' => '12:00',
        'is_active' => true,
    ]);

    $bookingDate = now()->next(Carbon\Carbon::FRIDAY);

    // Create a pending booking (should NOT block the slot)
    Booking::factory()->create([
        'user_id' => $this->user->id,
        'booking_date' => $bookingDate,
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'pending',
    ]);

    $slots = $this->service->getAvailableSlotsForDate(
        $this->user,
        $bookingDate,
        showUnavailable: false
    );

    $slotTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $slots);

    // The 10:00-11:00 slot should still be available
    expect($slotTimes)->toContain('10:00-11:00', '10:30-11:30');
});

test('it excludes slots blocked by restrictions', function () {
    Availability::factory()->create([
        'user_id' => $this->user->id,
        'day_of_week' => 5, // Friday
        'start_time' => '09:00',
        'end_time' => '17:00',
        'is_active' => true,
    ]);

    $date = now()->next(Carbon\Carbon::FRIDAY);

    // Create a restriction that blocks 12:00-13:00
    Restriction::factory()->create([
        'user_id' => $this->user->id,
        'start_date' => $date,
        'end_date' => $date,
        'start_time' => '12:00',
        'end_time' => '13:00',
        'type' => 'break',
        'reason' => 'Lunch Break',
    ]);

    $slots = $this->service->getAvailableSlotsForDate(
        $this->user,
        $date,
        showUnavailable: false
    );

    $slotTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $slots);

    // Slots that overlap with the restriction should NOT be available
    expect($slotTimes)->not->toContain('11:30-12:30', '12:00-13:00', '12:30-13:30');

    // Slots before the restriction should be available
    expect($slotTimes)->toContain('11:00-12:00');
});

test('it returns empty array when no availability defined', function () {
    $date = now()->next(Carbon\Carbon::FRIDAY);

    $slots = $this->service->getAvailableSlotsForDate(
        $this->user,
        $date,
        showUnavailable: false
    );

    expect($slots)->toBeEmpty();
});

test('it handles week view with bookings correctly', function () {
    // Setup availability for all days
    foreach (range(0, 6) as $day) {
        Availability::factory()->create([
            'user_id' => $this->user->id,
            'day_of_week' => $day,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);
    }

    $startOfWeek = now()->startOfWeek(); // Sunday

    // Create bookings on multiple days
    Booking::factory()->create([
        'user_id' => $this->user->id,
        'booking_date' => $startOfWeek->copy()->addDays(1), // Monday
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'confirmed',
    ]);

    Booking::factory()->create([
        'user_id' => $this->user->id,
        'booking_date' => $startOfWeek->copy()->addDays(3), // Wednesday
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
        'status' => 'confirmed',
    ]);

    // Check slots for the entire week
    $weekSlots = $this->service->getAvailableSlotsForWeek(
        $this->user,
        $startOfWeek,
        showUnavailable: false
    );

    expect($weekSlots)->toHaveCount(7); // 7 days in the week

    // Check Monday slots
    $mondaySlots = collect($weekSlots)->firstWhere('date', $startOfWeek->copy()->addDays(1)->toDateString())['slots'];
    $mondayTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $mondaySlots);
    expect($mondayTimes)->not->toContain('10:00-11:00', '10:30-11:30');

    // Check Wednesday slots
    $wednesdaySlots = collect($weekSlots)->firstWhere('date', $startOfWeek->copy()->addDays(3)->toDateString())['slots'];
    $wednesdayTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $wednesdaySlots);
    expect($wednesdayTimes)->not->toContain('14:00-15:00', '14:30-15:30');
});
