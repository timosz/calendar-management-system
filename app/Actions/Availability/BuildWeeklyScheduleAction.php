<?php

namespace App\Actions\Availability;

use App\Http\Resources\AvailabilityResource;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildWeeklyScheduleAction
{
    /**
     * Build a weekly schedule array for the given user.
     *
     * @param User $user
     * @return array
     */
    public function execute(User $user): array
    {
        // Get all availabilities for the user, indexed by day_of_week
        $availabilities = $user->availabilities()
            ->get()
            ->keyBy('day_of_week');

        return $this->buildScheduleFromAvailabilities($availabilities);
    }

    /**
     * Build schedule array from availabilities collection.
     *
     * @param Collection $availabilities
     * @return array
     */
    protected function buildScheduleFromAvailabilities(Collection $availabilities): array
    {
        $dayOrder = $this->getDayOrder();

        return collect($dayOrder)->map(function ($dayNumber) use ($availabilities) {
            $availability = $availabilities->get($dayNumber);

            return $availability
                ? AvailabilityResource::make($availability)->resolve()
                : $this->getEmptyDayData($dayNumber);
        })->toArray();
    }

    /**
     * Get empty day data when no availability exists.
     *
     * @param int $dayNumber
     * @return array
     */
    protected function getEmptyDayData(int $dayNumber): array
    {
        $dayNames = Availability::getDayNames();

        return [
            'day_of_week' => $dayNumber,
            'day_name' => $dayNames[$dayNumber],
            'is_active' => false,
            'start_time' => null,
            'end_time' => null,
            'id' => null,
        ];
    }

    /**
     * Get the day order (Monday to Sunday).
     *
     * @return array
     */
    protected function getDayOrder(): array
    {
        // Start from Monday (1) and include Sunday (0) at the end
        return [1, 2, 3, 4, 5, 6, 0];
    }
}
