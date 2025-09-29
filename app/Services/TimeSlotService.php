<?php

namespace App\Services;

class TimeSlotService
{
    /**
     * Generate time slots for a given interval
     *
     * @param int $intervalMinutes The interval in minutes (default: 15)
     * @param int $startHour Starting hour (default: 0)
     * @param int $endHour Ending hour (default: 24)
     * @return array
     */
    public function generateTimeSlots(int $intervalMinutes = 15, int $startHour = 0, int $endHour = 24): array
    {
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
}
