<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(
        private TimeSlotService $timeSlotService,
        private SlotAvailabilityChecker $availabilityChecker
    ) {
    }

    /**
     * Get available slots for a specific date
     */
    public function getAvailableSlotsForDate(
        User $user,
        Carbon $date,
        bool $showUnavailable = true,
        ?Collection $availabilities = null,
        ?Collection $restrictions = null,
        ?Collection $bookings = null
    ): array {
        $dayOfWeek = $date->dayOfWeek;

        // Get the availability for this day from provided collection or query
        $availability = $availabilities
            ? $availabilities->firstWhere('day_of_week', $dayOfWeek)
            : $user->availabilities()->active()->forDay($dayOfWeek)->first();

        // If no availability for this day, return empty
        if (!$availability) {
            return [];
        }

        // Generate all possible slots using config values
        $slots = $this->timeSlotService->generateSlots(
            $availability->start_time,
            $availability->end_time,
            config('booking.slot_interval_minutes'),
            config('booking.slot_duration_minutes')
        )->toArray();

        // Get restrictions and bookings for this date
        $dateRestrictions = $this->getDateRestrictions($user, $date, $restrictions);
        $dateBookings = $this->getDateBookings($user, $date, $bookings);

        // Check availability for all slots
        $processedSlots = $this->availabilityChecker->checkSlots(
            $slots,
            $date,
            $dateRestrictions,
            $dateBookings
        );

        // Filter out unavailable slots if requested
        if (!$showUnavailable) {
            $processedSlots = $this->availabilityChecker->filterAvailable($processedSlots);
        }

        return $processedSlots;
    }

    /**
     * Get available slots for a week (OPTIMIZED VERSION)
     */
    public function getAvailableSlotsForWeek(User $user, Carbon $startOfWeek, bool $showUnavailable = true): array
    {
        $endOfWeek = $startOfWeek->copy()->addDays(6);

        // Eager load all data needed for the entire week in 3 queries
        $availabilities = $user->availabilities()
            ->active()
            ->get()
            ->keyBy('day_of_week');

        $restrictions = $user->restrictions()
            ->affectingDateRange($startOfWeek, $endOfWeek)
            ->get();

        $bookings = $user->bookings()
            ->whereBetween('booking_date', [
                $startOfWeek->toDateString(),
                $endOfWeek->toDateString()
            ])
            ->confirmed()
            ->get()
            ->groupBy(function ($booking) {
                return $booking->booking_date->toDateString();
            });

        // Process each day
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

            // Get bookings for this specific date from the pre-loaded collection
            $dateBookings = $bookings->get($date->toDateString(), collect());

            $slots[] = [
                'date' => $date->toDateString(),
                'slots' => $this->getAvailableSlotsForDate(
                    $user,
                    $date,
                    $showUnavailable,
                    $availabilities,
                    $restrictions,
                    $dateBookings
                ),
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

    /**
     * Get restrictions for a specific date
     */
    private function getDateRestrictions(User $user, Carbon $date, ?Collection $restrictions): Collection
    {
        return $restrictions
            ? $restrictions->filter(fn ($r) => $r->affectsDate($date))
            : $user->restrictions()->affectingDateRange($date, $date)->get();
    }

    /**
     * Get bookings for a specific date
     */
    private function getDateBookings(User $user, Carbon $date, ?Collection $bookings): Collection
    {
        return $bookings
            ? $bookings->filter(fn ($booking) => $booking->booking_date->isSameDay($date))
            : $user->bookings()
                ->whereDate('booking_date', $date->toDateString())
                ->confirmed()
                ->get();
    }
}
