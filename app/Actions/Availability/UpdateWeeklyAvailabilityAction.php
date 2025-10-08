<?php

namespace App\Actions\Availability;

use App\Models\Availability;
use Illuminate\Support\Facades\DB;

class UpdateWeeklyAvailabilityAction
{
    /**
     * Update the weekly availability schedule for a user.
     *
     * @param int $userId
     * @param array $availabilities
     * @return void
     */
    public function execute(int $userId, array $availabilities): void
    {
        DB::transaction(function () use ($userId, $availabilities) {
            foreach ($availabilities as $availabilityData) {
                $this->updateOrDeleteAvailability($userId, $availabilityData);
            }
        });
    }

    /**
     * Update or delete availability for a specific day.
     *
     * @param int $userId
     * @param array $availabilityData
     * @return void
     */
    protected function updateOrDeleteAvailability(int $userId, array $availabilityData): void
    {
        $dayOfWeek = $availabilityData['day_of_week'];

        // Find existing availability for this day
        $availability = Availability::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($availabilityData['is_active']) {
            // Create or update availability
            $data = [
                'user_id' => $userId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $availabilityData['start_time'],
                'end_time' => $availabilityData['end_time'],
                'is_active' => true,
            ];

            if ($availability) {
                $availability->update($data);
            } else {
                Availability::create($data);
            }
        } else {
            // If not active, delete the availability if it exists
            $availability?->delete();
        }
    }
}
