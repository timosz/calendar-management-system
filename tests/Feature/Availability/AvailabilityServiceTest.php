<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Restriction;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AvailabilityService::class);
    $this->user = User::factory()->create();

    // Set a consistent "now" for all tests
    Carbon::setTestNow('2025-01-13 10:00:00'); // A Monday
});

afterEach(function () {
    Carbon::setTestNow(); // Clear the test now
});

describe('getAvailableSlotsForDate', function () {
    test('returns empty array when no availability exists for the day', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        expect($slots)->toBeArray()->toBeEmpty();
    });

    test('returns empty array when availability is inactive', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        Availability::factory()
            ->inactive()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        expect($slots)->toBeArray()->toBeEmpty();
    });

    test('generates slots based on config settings', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        // Create availability from 9:00 to 17:00
        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        // With 30min interval and 60min duration:
        // 09:00-10:00, 09:30-10:30, 10:00-11:00, ..., 16:00-17:00
        // Last slot starts at 16:00 to end at 17:00
        // Total slots = 15 (every 30 minutes from 9:00 to 16:00 inclusive)
        expect($slots)->toBeArray()->toHaveCount(15);

        // Check first and last slot
        expect($slots[0])->toEqual([
            'start_time' => '09:00',
            'end_time' => '10:00',
            'available' => true,
        ]);

        expect($slots[14])->toEqual([
            'start_time' => '16:00',
            'end_time' => '17:00',
            'available' => true,
        ]);
    });

    test('marks slots as unavailable when conflicting with confirmed booking', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        // Create availability from 9:00 to 17:00
        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Simulate a confirmed booking from 10:00 to 11:00
        Booking::factory()
            ->confirmed()
            ->forDate($date->toDateString())
            ->atTime('10:00', '11:00')
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        // Slots overlapping with the booking should be marked unavailable
        $unavailableSlots = array_filter($slots, fn ($slot) => !$slot['available']);

        expect($unavailableSlots)->not->toBeEmpty();

        // Check that the 10:00-11:00 slot is unavailable
        $tenToElevenSlot = collect($slots)->firstWhere('start_time', '10:00');

        expect($tenToElevenSlot['available'])->toBeFalse();
    });

    test('ignores pending bookings when checking conflicts', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Create a pending booking from 11:00 to 12:00
        Booking::factory()
            ->pending()
            ->forDate($date->toDateString())
            ->atTime('11:00', '12:00')
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        // All slots should be available since pending bookings are ignored
        $availableSlots = array_filter($slots, fn ($slot) => $slot['available']);

        expect($availableSlots)->toHaveCount(count($slots));
    });

    test('marks slots as unavailable when conflicting with all-day restriction', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Create an all-day restriction on this date
        Restriction::factory()
            ->allDay()
            ->forDate($date->toDateString())
            ->holiday('Public Holiday')
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        // All slots should be unavailable due to the all-day restriction
        $availableSlots = array_filter($slots, fn ($slot) => $slot['available']);

        expect($availableSlots)->toBeEmpty();

        // Check that reason is provided
        expect($slots[0]['reason'])->toBe('Public Holiday');
    });

    test('marks slots as unavailable when conflicting with partial-day restriction', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Lunch break 12:00-13:00
        Restriction::factory()
            ->lunchBreak()
            ->forDate($date->toDateString())
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        // Find slots overlapping with the restriction
        $lunchSlots = array_filter($slots, function ($slot) {
            return $slot['start_time'] >= '11:30' && $slot['start_time'] <= '12:30';
        });

        // At least some lunch slots should be unavailable
        $unavailableLunchSlots = array_filter($lunchSlots, fn ($slot) => !$slot['available']);

        expect($unavailableLunchSlots)->not->toBeEmpty();
    });

    test('filters out unavailable slots when showUnavailable is false', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Create a confirmed booking from 10:00 to 11:00
        Booking::factory()
            ->confirmed()
            ->forDate($date->toDateString())
            ->atTime('10:00', '11:00')
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date, showUnavailable: false);

        // All returned slots should be available
        foreach ($slots as $slot) {
            expect($slot['available'])->toBeTrue();
        }

        // Should have fewer slots than with showUnavailable = true
        $allSlots = $this->service->getAvailableSlotsForDate($this->user, $date, showUnavailable: true);
        expect(count($slots))->toBeLessThan(count($allSlots));
    });

    test('handles multiple restrictions on same day', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Morning meeting 09:00-10:00
        Restriction::factory()
            ->meeting()
            ->forDate($date->toDateString())
            ->partialDay('09:00', '10:00')
            ->for($this->user)
            ->create();

        // Lunch break 12:00-13:00
        Restriction::factory()
            ->lunchBreak()
            ->forDate($date->toDateString())
            ->for($this->user)
            ->create();

        // Afternoon maintenance 15:00-16:00
        Restriction::factory()
            ->maintenance()
            ->forDate($date->toDateString())
            ->partialDay('15:00', '16:00')
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date, showUnavailable: false);

        // Should have slots, but not during the three restriction periods
        expect($slots)->not->toBeEmpty();

        // Check that no slot falls within the restricted times
        foreach ($slots as $slot) {
            expect($slot['available'])->toBeTrue();

            expect(($slot['start_time'] < '08:30' || $slot['start_time'] > '09:30') &&
                   ($slot['start_time'] < '11:30' || $slot['start_time'] > '12:30') &&
                   ($slot['start_time'] < '14:30' || $slot['start_time'] > '15:30'))->toBeTrue();

            expect($slot['start_time'])->not->toBe('09:00');
            expect($slot['start_time'])->not->toBe('12:00');
            expect($slot['start_time'])->not->toBe('15:00');
        }
    });

    test('uses provided collections instead of querying database', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        $availability = Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->make(['user_id' => $this->user->id]);

        $availabilities = collect([$availability]);

        $restrictions = collect();
        $bookings = collect();

        // This should work without querying the database
        $slots = $this->service->getAvailableSlotsForDate(
            $this->user,
            $date,
            showUnavailable: true,
            availabilities: $availabilities,
            restrictions: $restrictions,
            bookings: $bookings
        );

        expect($slots)->toBeArray()->not->toBeEmpty();
    });
});

describe('getAvailableSlotsForWeek', function () {
    test('returns 7 days of slots', function () {
        $startOfWeek = Carbon::parse('2025-01-13'); // Monday

        // Create availability for all 7 days
        for ($day = 0; $day <= 6 ; $day++) {
            Availability::factory()
                ->active()
                ->forDay($day)
                ->businessHours()
                ->for($this->user)
                ->create();
        }

        $result = $this->service->getAvailableSlotsForWeek($this->user, $startOfWeek);

        expect($result)->toBeArray()->toHaveCount(7);
        expect($result[0]['date'])->toBe('2025-01-13'); // Monday
        expect($result[6]['date'])->toBe('2025-01-19'); // Sunday
    });

    test('skips past dates', function () {
        Carbon::setTestNow('2025-01-15 10:00:00'); // A Wednesday
        $startOfWeek = Carbon::parse('2025-01-13'); // Monday (2 days ago)

        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        $result = $this->service->getAvailableSlotsForWeek($this->user, $startOfWeek);

        // Monday and Tuesday should be skipped
        expect($result[0]['slots'])->toBeEmpty(); // Monday
        expect($result[1]['slots'])->toBeEmpty(); // Tuesday
    });

    test('includes today', function () {
        $startOfWeek = Carbon::parse('2025-01-13'); // Monday

        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        $result = $this->service->getAvailableSlotsForWeek($this->user, $startOfWeek);

        // Today should have slots
        expect($result[0]['slots'])->not->toBeEmpty(); // Monday
    });

    test('optimizes queries by eager loading data', function () {
        $startOfWeek = Carbon::parse('2025-01-13'); // Monday

        // Create availability for Monday and Wednesday
        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        Availability::factory()
            ->active()
            ->forDay(3) // Wednesday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Create restrictions and bookings across the week
        Restriction::factory()
            ->lunchBreak()
            ->forDateRange('2025-01-13', '2025-01-19')
            ->for($this->user)
            ->create();

        Booking::factory()
            ->confirmed()
            ->forDate('2025-01-13') // Monday
            ->atTime('10:00', '11:00')
            ->for($this->user)
            ->create();

        // Enable query log
        DB::enableQueryLog();

        $this->service->getAvailableSlotsForWeek($this->user, $startOfWeek);

        $queries = DB::getQueryLog();

        // Should only have 3 main queries :
        // 1. Load availabilities
        // 2. Load restrictions
        // 3. Load bookings
        // (plus any auth/session related queries)
        expect(count($queries))->toBeLessThanOrEqual(6);
    });

    test('handles week with mixed availability', function () {
        $startOfWeek = Carbon::parse('2025-01-13'); // Monday

        // Monday : full day availability
        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Tuesday : Morning only
        Availability::factory()
            ->active()
            ->forDay(2) // Tuesday
            ->morningOnly()
            ->for($this->user)
            ->create();

        // Wednesday : Inactive
        Availability::factory()
            ->inactive()
            ->forDay(3) // Wednesday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Thursday-Sunday : No availability

        $result = $this->service->getAvailableSlotsForWeek($this->user, $startOfWeek);

        // Monday should have full day slots
        expect($result[0]['slots'])->not->toBeEmpty(); // Monday

        // Tuesday should have morning slots only
        expect($result[1]['slots'])->not->toBeEmpty(); // Tuesday
        $tuesdayLatestSlot = end($result[1]['slots']);
        expect($tuesdayLatestSlot['end_time'])->toBeLessThanOrEqual('12:00');

        // Wednesday should have no slots
        expect($result[2]['slots'])->toBeEmpty(); // Wednesday

        // Rest of the week should have no slots
        for ($i = 3; $i <= 6; $i++) {
            expect($result[$i]['slots'])->toBeEmpty();
        }
    });

    test('respects showUnavailable parameter across all days', function () {
        $startOfWeek = Carbon::parse('2025-01-13'); // Monday

        // Create availability for Monday
        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Create booking that blocks some slots
        Booking::factory()
            ->confirmed()
            ->forDate('2025-01-13') // Monday
            ->atTime('10:00', '11:00')
            ->for($this->user)
            ->create();

        $resultWithUnavailable = $this->service->getAvailableSlotsForWeek($this->user, $startOfWeek, showUnavailable: true);

        $resultWithoutUnavailable = $this->service->getAvailableSlotsForWeek($this->user, $startOfWeek, showUnavailable: false);

        // Monday should have more slots when showing unavailable
        expect(count($resultWithUnavailable[0]['slots']))->toBeGreaterThan(count($resultWithoutUnavailable[0]['slots']));

        // All slots in fitered result should be available
        foreach ($resultWithoutUnavailable[0]['slots'] as $slot) {
            expect($slot['available'])->toBeTrue();
        }
    });
});

describe('getMaxWeeksAhead', function () {
    test('returns configured max weeks ahead', function () {
        config(['booking.max_weeks_ahead' => 8]);

        $maxWeeks = $this->service->getMaxWeeksAhead();

        expect($maxWeeks)->toBe(8);
    });

    test('handles different configuration values', function () {
        config(['booking.max_weeks_ahead' => 4]);
        expect($this->service->getMaxWeeksAhead())->toBe(4);

        config(['booking.max_weeks_ahead' => 12]);
        expect($this->service->getMaxWeeksAhead())->toBe(12);

        config(['booking.max_weeks_ahead' => 0]);
        expect($this->service->getMaxWeeksAhead())->toBe(0);
    });
});

describe('edge cases', function () {

    test('handles slots at day boundaries correctly', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        // Availability ending exactly at midnight
        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->state([
                'start_time' => '22:00',
                'end_time' => '23:59',
            ])
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        expect($slots)->toBeArray()->not->toBeEmpty();

        // Last slot should not exceed end_time
        $lastSlot = end($slots);
        expect($lastSlot['end_time'])->toBeLessThanOrEqual('23:59');
    });

    test('handles booking that spans exactly one slot', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Booking that exactly matches slot duration (e.g., 60 minutes)
        Booking::factory()
            ->confirmed()
            ->forDate($date->toDateString())
            ->atTime('10:00', '11:00')
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        // The 10:00-11:00 slot should be unavailable
        $targetSlot = collect($slots)->firstWhere('start_time', '10:00');

        expect($targetSlot['available'])->toBeFalse();
    });

    test('handles restriction that spans multiple days', function () {
        $startOfWeek = Carbon::parse('2025-01-13'); // Monday

        // Create availability for all weekdays
        for ($day = 1; $day <= 5 ; $day++) {
            Availability::factory()
                ->active()
                ->forDay($day)
                ->businessHours()
                ->for($this->user)
                ->create();
        }

        // Create restriction from Wednesday to Friday
        Restriction::factory()
            ->allDay()
            ->holiday('Mid-week break')
            ->forDateRange('2025-01-15', '2025-01-17') // Wed to Fri
            ->for($this->user)
            ->create();

        $result = $this->service->getAvailableSlotsForWeek($this->user, $startOfWeek);

        // Monday and Tuesday should have slots
        expect($result[0]['slots'])->not->toBeEmpty(); // Monday
        expect($result[1]['slots'])->not->toBeEmpty(); // Tuesday

        // Wednesday to Friday should have no slots
        $wednesdayAvailable = array_filter($result[2]['slots'], fn ($slot) => $slot['available']);
        $thursdayAvailable = array_filter($result[3]['slots'], fn ($slot) => $slot['available']);
        $fridayAvailable = array_filter($result[4]['slots'], fn ($slot) => $slot['available']);

        expect($wednesdayAvailable)->toBeEmpty();
        expect($thursdayAvailable)->toBeEmpty();
        expect($fridayAvailable)->toBeEmpty();
    });

    test('handles overlapping bookings correctly', function () {
        $date = Carbon::parse('2025-01-13'); // Monday

        Availability::factory()
            ->active()
            ->forDay(1) // Monday
            ->businessHours()
            ->for($this->user)
            ->create();

        // Two bookings that do not overlap but are back-to-back
        Booking::factory()
            ->confirmed()
            ->forDate($date->toDateString())
            ->atTime('10:00', '11:00')
            ->for($this->user)
            ->create();

        Booking::factory()
            ->confirmed()
            ->forDate($date->toDateString())
            ->atTime('11:00', '12:00')
            ->for($this->user)
            ->create();

        $slots = $this->service->getAvailableSlotsForDate($this->user, $date);

        // Both hour-long slots should be unavailable
        $tenToElevenSlot = collect($slots)->firstWhere('start_time', '10:00');
        $elevenToTwelveSlot = collect($slots)->firstWhere('start_time', '11:00');

        expect($tenToElevenSlot['available'])->toBeFalse();
        expect($elevenToTwelveSlot['available'])->toBeFalse();

        // 09:00 and 12:00 slots should be available
        $nineToTenSlot = collect($slots)->firstWhere('start_time', '09:00');
        $twelveToOneSlot = collect($slots)->firstWhere('start_time', '12:00');

        expect($nineToTenSlot['available'])->toBeTrue();
        expect($twelveToOneSlot['available'])->toBeTrue();
    });
});
