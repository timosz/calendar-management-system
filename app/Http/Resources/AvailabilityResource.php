<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->day_name,
            'start_time' => $this->formatTime($this->start_time),
            'end_time' => $this->formatTime($this->end_time),
            'is_active' => $this->is_active,
        ];
    }

    /**
     * Format time string to H:i format.
     *
     * @param string|null $time
     * @return string|null
     */
    protected function formatTime(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        // If already in H:i format, return as is
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        // Otherwise, parse and format
        return date('H:i', strtotime($time));
    }
}
