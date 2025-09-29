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

                // Format label in 12-hour format with AM/PM
                $period = $hour >= 12 ? 'PM' : 'AM';
                $displayHour = $hour === 0 ? 12 : ($hour > 12 ? $hour - 12 : $hour);
                $label = sprintf('%d:%02d %s', $displayHour, $minute, $period);

                $timeSlots[] = [
                    'value' => $time,
                    'label' => $label,
                ];
            }
        }

        // Add end time if it's 24:00
        if ($endHour === 24) {
            $timeSlots[] = [
                'value' => '24:00',
                'label' => '12:00 AM (Next Day)',
            ];
        }

        return $timeSlots;
    }
}
