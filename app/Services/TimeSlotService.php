<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class TimeSlotService
{
    /**
     * Generate time slots for dropdowns/selectors
     */
    public function generateTimeOptions(
        int $intervalMinutes = 15,
        int $startHour = 0,
        int $endHour = 24
    ): array {
        $timeSlots = [];

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $intervalMinutes) {
                $time = sprintf('%02d:%02d', $hour, $minute);

                $timeSlots[] = [
                    'value' => $time,
                    'label' => $time,
                ];
            }
        }

        return $timeSlots;
    }

    /**
     * Generate bookable time slots between start and end time
     */
    public function generateSlots(
        string $startTime,
        string $endTime,
        int $intervalMinutes,
        int $slotDurationMinutes
    ): Collection {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime)->subMinutes($slotDurationMinutes);

        $period = CarbonPeriod::create(
            $start,
            "{$intervalMinutes} minutes",
            $end
        );

        return collect($period)->map(function ($slotStart) use ($slotDurationMinutes) {
            $slotEnd = $slotStart->copy()->addMinutes($slotDurationMinutes);

            return [
                'start_time' => $slotStart->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
            ];
        });
    }
}
