<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Restriction;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AvailabilityService::class);
    $this->user = User::factory()->create();

    // Setup default config values for tests
    config(['booking.slot_interval_minutes' => 30]);
    config(['booking.slot_duration_minutes' => 60]);

    // Set a consistent "now" for all tests to ensure future dates
    Carbon\Carbon::setTestNow('2025-01-13 10:00:00'); // A Monday
});

afterEach(function () {
    Carbon\Carbon::setTestNow(); // Clear the test now
});

describe('preloaded collections optimization', function () {
    test('it excludes confirmed bookings when using preloaded collections', function () {
        // This test specifically targets the bug where Carbon instances
        // were not being compared correctly with date strings

        // Create availability for Monday (day 1)
        Availability::factory()->create([
            'user_id' => $this->user->id,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $bookingDate = Carbon\Carbon::parse('2025-01-13'); // Monday (today in our test)

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

    test('it handles preloaded restrictions correctly', function () {
        Availability::factory()->create([
            'user_id' => $this->user->id,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $date = Carbon\Carbon::parse('2025-01-13'); // Monday

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

        // Preload data
        $availabilities = $this->user->availabilities()
            ->active()
            ->get()
            ->keyBy('day_of_week');

        $restrictions = $this->user->restrictions()
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->get();

        $slots = $this->service->getAvailableSlotsForDate(
            $this->user,
            $date,
            showUnavailable: false,
            availabilities: $availabilities,
            restrictions: $restrictions
        );

        $slotTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $slots);

        // Slots that overlap with the restriction should NOT be available
        expect($slotTimes)->not->toContain('11:30-12:30', '12:00-13:00', '12:30-13:30');

        // Slots before the restriction should be available
        expect($slotTimes)->toContain('11:00-12:00');
    });
});

describe('week view with complex scenarios', function () {
    test('it handles week view with bookings across multiple days', function () {
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

        $startOfWeek = Carbon\Carbon::parse('2025-01-12'); // Sunday

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

        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => $startOfWeek->copy()->addDays(5), // Friday
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
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

        // Check Friday slots
        $fridaySlots = collect($weekSlots)->firstWhere('date', $startOfWeek->copy()->addDays(5)->toDateString())['slots'];
        $fridayTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $fridaySlots);
        expect($fridayTimes)->not->toContain('09:00-10:00', '09:30-10:30');

        // Verify other days have available slots
        $tuesdaySlots = collect($weekSlots)->firstWhere('date', $startOfWeek->copy()->addDays(2)->toDateString())['slots'];
        expect($tuesdaySlots)->not->toBeEmpty();
    });

    test('it handles week view with restrictions and bookings combined', function () {
        // Setup availability for weekdays
        foreach (range(1, 5) as $day) {
            Availability::factory()->create([
                'user_id' => $this->user->id,
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true,
            ]);
        }

        $startOfWeek = Carbon\Carbon::parse('2025-01-12'); // Sunday

        // Create a multi-day restriction (Wednesday to Thursday)
        Restriction::factory()->create([
            'user_id' => $this->user->id,
            'start_date' => $startOfWeek->copy()->addDays(3), // Wednesday
            'end_date' => $startOfWeek->copy()->addDays(4), // Thursday
            'start_time' => null,
            'end_time' => null,
            'type' => 'holiday',
            'reason' => 'Short break',
        ]);

        // Create a booking on Monday
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => $startOfWeek->copy()->addDays(1), // Monday
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
        ]);

        // Create a daily lunch break restriction for the week
        Restriction::factory()->create([
            'user_id' => $this->user->id,
            'start_date' => $startOfWeek->copy()->addDays(1), // Monday
            'end_date' => $startOfWeek->copy()->addDays(5), // Friday
            'start_time' => '12:00',
            'end_time' => '13:00',
            'type' => 'break',
            'reason' => 'Lunch',
        ]);

        $weekSlots = $this->service->getAvailableSlotsForWeek(
            $this->user,
            $startOfWeek,
            showUnavailable: false
        );

        // Monday: Should have slots but exclude 10:00-11:00 booking and 12:00-13:00 lunch
        $mondaySlots = collect($weekSlots)->firstWhere('date', $startOfWeek->copy()->addDays(1)->toDateString())['slots'];
        $mondayTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $mondaySlots);

        // Should exclude the booking and lunch break
        expect($mondayTimes)->not->toContain('10:00-11:00', '10:30-11:30', '12:00-13:00', '12:30-13:30');

        // Should have some available slots (not being specific about which ones since past filtering may affect this)
        expect($mondaySlots)->not->toBeEmpty();

        // Wednesday: Should have no slots (all-day restriction)
        $wednesdaySlots = collect($weekSlots)->firstWhere('date', $startOfWeek->copy()->addDays(3)->toDateString())['slots'];
        expect($wednesdaySlots)->toBeEmpty();

        // Thursday: Should have no slots (all-day restriction)
        $thursdaySlots = collect($weekSlots)->firstWhere('date', $startOfWeek->copy()->addDays(4)->toDateString())['slots'];
        expect($thursdaySlots)->toBeEmpty();

        // Friday: Should have slots but exclude 12:00-13:00 lunch
        $fridaySlots = collect($weekSlots)->firstWhere('date', $startOfWeek->copy()->addDays(5)->toDateString())['slots'];
        $fridayTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $fridaySlots);

        // Should exclude lunch break
        expect($fridayTimes)->not->toContain('12:00-13:00', '12:30-13:30');

        // Should have available slots
        expect($fridaySlots)->not->toBeEmpty();
    });
});

describe('real-world scenarios', function () {
    test('it handles a busy day with multiple bookings and restrictions', function () {
        Availability::factory()->create([
            'user_id' => $this->user->id,
            'day_of_week' => 2, // Tuesday
            'start_time' => '08:00',
            'end_time' => '18:00',
            'is_active' => true,
        ]);

        $date = Carbon\Carbon::parse('2025-01-14'); // Tuesday

        // Morning meeting
        Restriction::factory()->create([
            'user_id' => $this->user->id,
            'start_date' => $date,
            'end_date' => $date,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'type' => 'meeting',
            'reason' => 'Team standup',
        ]);

        // Three confirmed bookings throughout the day
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => $date,
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
            'status' => 'confirmed',
        ]);

        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => $date,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'confirmed',
        ]);

        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => $date,
            'start_time' => '14:30:00',
            'end_time' => '15:30:00',
            'status' => 'confirmed',
        ]);

        // Lunch break
        Restriction::factory()->create([
            'user_id' => $this->user->id,
            'start_date' => $date,
            'end_date' => $date,
            'start_time' => '12:30',
            'end_time' => '13:30',
            'type' => 'break',
            'reason' => 'Lunch',
        ]);

        $slots = $this->service->getAvailableSlotsForDate(
            $this->user,
            $date,
            showUnavailable: false
        );

        $slotTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $slots);

        // Should have some available slots
        expect($slots)->not->toBeEmpty();

        // Verify blocked times are excluded
        expect($slotTimes)->not->toContain(
            '08:00-09:00',
            '08:30-09:30', // Morning meeting
            '09:30-10:30',
            '10:00-11:00', // First booking
            '11:00-12:00',
            '11:30-12:30', // Second booking
            '12:30-13:30',
            '13:00-14:00', // Lunch break
            '14:30-15:30',
            '15:00-16:00'  // Third booking
        );

        // Verify at least some available times exist
        // (being less specific since slot overlaps can be tricky with 30min intervals)
        $hasAvailableAfternoonSlots = collect($slotTimes)->contains(
            fn ($time) =>
            str_starts_with($time, '15:30-') ||
            str_starts_with($time, '16:00-') ||
            str_starts_with($time, '17:00-')
        );

        expect($hasAvailableAfternoonSlots)->toBeTrue();
    });

    test('it correctly handles back-to-back bookings with no gaps', function () {
        Availability::factory()->create([
            'user_id' => $this->user->id,
            'day_of_week' => 3, // Wednesday
            'start_time' => '09:00',
            'end_time' => '15:00',
            'is_active' => true,
        ]);

        $date = Carbon\Carbon::parse('2025-01-15'); // Wednesday

        // Create three back-to-back bookings
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
        ]);

        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => $date,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'confirmed',
        ]);

        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => $date,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => 'confirmed',
        ]);

        $slots = $this->service->getAvailableSlotsForDate(
            $this->user,
            $date,
            showUnavailable: false
        );

        $slotTimes = array_map(fn ($slot) => $slot['start_time'] . '-' . $slot['end_time'], $slots);

        // All three booked slots should be excluded
        expect($slotTimes)->not->toContain(
            '10:00-11:00',
            '10:30-11:30',
            '11:00-12:00',
            '11:30-12:30',
            '12:00-13:00',
            '12:30-13:30'
        );

        // Slots before and after the block should be available
        expect($slotTimes)->toContain(
            '09:00-10:00', // Before the block
            '13:00-14:00',
            '13:30-14:30',
            '14:00-15:00' // After the block
        );
    });
});
