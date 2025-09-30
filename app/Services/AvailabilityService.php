<?php

namespace App\Services;

use App\Models\User;
use App\Models\Booking;
use App\Models\Restriction;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Get available slots for a specific date
     */
    public function getAvailableSlotsForDate(User $user, Carbon $date, bool $showUnavailable = true): array
    {
        $dayOfWeek = $date->dayOfWeek;

        // Get the availability for this day
        $availability = $user->availabilities()
            ->active()
            ->forDay($dayOfWeek)
            ->first();

        // If no availability for this day, return empty
        if (!$availability) {
            return [];
        }

        // Generate all possible slots using config values
        $slots = $this->generateTimeSlots(
            $availability->start_time,
            $availability->end_time,
            config('booking.slot_interval_minutes'),
            config('booking.slot_duration_minutes')
        );

        // Get restrictions for this date
        $restrictions = $user->restrictions()
            ->affectingDateRange($date, $date)
            ->get();

        // Get confirmed bookings for this date
        $confirmedBookings = $user->bookings()
            ->where('booking_date', $date->toDateString())
            ->confirmed()
            ->get();

        // Mark slots as available/unavailable
        $processedSlots = array_map(function ($slot) use ($date, $restrictions, $confirmedBookings) {
            $startTime = $slot['start_time'];
            $endTime = $slot['end_time'];

            // Check if slot conflicts with any restriction
            foreach ($restrictions as $restriction) {
                if ($restriction->conflictsWithTimeRange($date, $startTime, $endTime)) {
                    return [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'available' => false,
                        'reason' => $restriction->reason ?: ucfirst($restriction->type),
                    ];
                }
            }

            // Check if slot conflicts with any confirmed booking
            foreach ($confirmedBookings as $booking) {
                if ($booking->conflictsWithTimeRange($startTime, $endTime)) {
                    return [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'available' => false,
                    ];
                }
            }

            // Slot is available
            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'available' => true,
            ];
        }, $slots);

        // Filter out unavailable slots if debug mode is off
        if (!$showUnavailable) {
            $processedSlots = array_filter($processedSlots, fn ($slot) => $slot['available']);
            // Re-index array to ensure sequential keys for JSON
            $processedSlots = array_values($processedSlots);
        }

        return $processedSlots;
    }

    /**
     * Generate time slots between start and end time
     */
    private function generateTimeSlots(
        string $startTime,
        string $endTime,
        int $intervalMinutes,
        int $slotDurationMinutes
    ): array {
        $slots = [];
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        $current = $start->copy();

        while ($current->copy()->addMinutes($slotDurationMinutes)->lessThanOrEqualTo($end)) {
            $slotEnd = $current->copy()->addMinutes($slotDurationMinutes);

            $slots[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
            ];

            $current->addMinutes($intervalMinutes);
        }

        return $slots;
    }

    /**
     * Get available slots for a week
     */
    public function getAvailableSlotsForWeek(User $user, Carbon $startOfWeek, bool $showUnavailable = true): array
    {
        $slots = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);

            // Skip past dates
            if ($date->isPast() && !$date->isToday()) {
                $slots[] = [
                    'date' => $date->toDateString(),
                    'slots' => [],
                ];
                continue;
            }

            $slots[] = [
                'date' => $date->toDateString(),
                'slots' => $this->getAvailableSlotsForDate($user, $date, $showUnavailable),
            ];
        }

        return $slots;
    }

    /**
     * Get the maximum number of weeks ahead for bookings
     */
    public function getMaxWeeksAhead(): int
    {
        return config('booking.max_weeks_ahead');
    }
}
