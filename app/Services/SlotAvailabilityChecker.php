<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotAvailabilityChecker
{
    /**
     * Check if a slot is available and mark it accordingly
     */
    public function checkSlot(
        array $slot,
        Carbon $date,
        Collection $restrictions,
        Collection $bookings
    ): array {
        $startTime = $slot['start_time'];
        $endTime = $slot['end_time'];

        // Check restrictions first (they have priority and provide reasons)
        if ($conflictingRestriction = $this->findConflictingRestriction($date, $startTime, $endTime, $restrictions)) {
            return $this->markSlotUnavailable($startTime, $endTime, $conflictingRestriction);
        }

        // Check booking conflicts
        if ($this->hasConflictingBooking($startTime, $endTime, $bookings)) {
            return $this->markSlotUnavailable($startTime, $endTime);
        }

        // Slot is available
        return $this->markSlotAvailable($startTime, $endTime);
    }

    /**
     * Find a restriction that conflicts with the given time range
     * Returns the restriction if found, null otherwise
     */
    private function findConflictingRestriction(
        Carbon $date,
        string $startTime,
        string $endTime,
        Collection $restrictions
    ): mixed {
        return $restrictions->first(
            fn ($restriction) => $restriction->conflictsWithTimeRange($date, $startTime, $endTime)
        );
    }

    /**
     * Check if there's any booking that conflicts with the given time range
     */
    private function hasConflictingBooking(
        string $startTime,
        string $endTime,
        Collection $bookings
    ): bool {
        return $bookings->contains(
            fn ($booking) => $booking->conflictsWithTimeRange($startTime, $endTime)
        );
    }

    /**
     * Mark a slot as unavailable
     */
    private function markSlotUnavailable(string $startTime, string $endTime, mixed $restriction = null): array
    {
        $slot = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'available' => false,
        ];

        // Add reason if it's a restriction
        if ($restriction) {
            $slot['reason'] = $restriction->reason ?: ucfirst($restriction->type);
        }

        return $slot;
    }

    /**
     * Mark a slot as available
     */
    private function markSlotAvailable(string $startTime, string $endTime): array
    {
        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'available' => true,
        ];
    }

    /**
     * Check multiple slots at once
     */
    public function checkSlots(
        array $slots,
        Carbon $date,
        Collection $restrictions,
        Collection $bookings
    ): array {
        return array_map(
            fn ($slot) => $this->checkSlot($slot, $date, $restrictions, $bookings),
            $slots
        );
    }

    /**
     * Filter out unavailable slots
     */
    public function filterAvailable(array $slots): array
    {
        return array_values(
            array_filter($slots, fn ($slot) => $slot['available'])
        );
    }
}
