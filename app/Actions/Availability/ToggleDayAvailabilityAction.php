<?php

namespace App\Actions\Availability;

use App\Models\Availability;

class ToggleDayAvailabilityAction
{
    /**
     * Toggle the active status for a specific day's availability.
     *
     * @param int $userId
     * @param int $dayOfWeek
     * @return array{success: bool, availability: Availability|null, message: string}
     */
    public function execute(int $userId, int $dayOfWeek): array
    {
        $availability = Availability::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$availability) {
            return [
                'success' => false,
                'availability' => null,
                'message' => 'No availability found for this day.',
            ];
        }

        $availability->update([
            'is_active' => !$availability->is_active,
        ]);

        $status = $availability->is_active ? 'activated' : 'deactivated';
        $dayName = $availability->day_name;

        return [
            'success' => true,
            'availability' => $availability,
            'message' => "{$dayName} availability {$status} successfully.",
        ];
    }
}
