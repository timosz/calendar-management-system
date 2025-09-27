<?php

namespace Database\Factories;

use App\Models\Restriction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restriction>
 */
class RestrictionFactory extends Factory
{
    protected $model = Restriction::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+6 months');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');

        // 50% chance of being all-day restriction
        $isAllDay = $this->faker->boolean(50);

        if ($isAllDay) {
            $startTime = null;
            $endTime = null;
        } else {
            $startHour = $this->faker->numberBetween(8, 16);
            $endHour = $this->faker->numberBetween($startHour + 1, 18);
            $startTime = sprintf('%02d:%02d', $startHour, $this->faker->randomElement([0, 15, 30, 45]));
            $endTime = sprintf('%02d:%02d', $endHour, $this->faker->randomElement([0, 15, 30, 45]));
        }

        return [
            'user_id' => User::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'reason' => $this->faker->optional(0.8)->sentence(),
            'type' => $this->faker->randomElement(['holiday', 'break', 'meeting', 'personal', 'maintenance', 'other']),
        ];
    }

    /**
     * Create a single day restriction
     */
    public function singleDay(): static
    {
        return $this->state(function (array $attributes) {
            $date = $this->faker->dateTimeBetween('now', '+3 months');
            return [
                'start_date' => $date->format('Y-m-d'),
                'end_date' => $date->format('Y-m-d'),
            ];
        });
    }

    /**
     * Create a multi-day restriction
     */
    public function multiDay(int $days = null): static
    {
        return $this->state(function (array $attributes) use ($days) {
            $startDate = $this->faker->dateTimeBetween('now', '+3 months');
            $daysToAdd = $days ?? $this->faker->numberBetween(2, 14);
            $endDate = Carbon::parse($startDate)->addDays($daysToAdd);

            return [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];
        });
    }

    /**
     * Create all-day restriction
     */
    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => null,
            'end_time' => null,
        ]);
    }

    /**
     * Create partial day restriction with specific times
     */
    public function partialDay(string $startTime = null, string $endTime = null): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime ?? '12:00',
            'end_time' => $endTime ?? '13:00',
        ]);
    }

    /**
     * Create lunch break restriction
     */
    public function lunchBreak(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '12:00',
            'end_time' => '13:00',
            'type' => 'break',
            'reason' => 'Lunch break',
        ]);
    }

    /**
     * Create holiday restriction
     */
    public function holiday(string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'holiday',
            'reason' => $reason ?? $this->faker->randomElement([
                'Christmas Holiday',
                'New Year Holiday',
                'Summer Vacation',
                'Spring Break',
                'Personal Holiday'
            ]),
            'start_time' => null,
            'end_time' => null,
        ]);
    }

    /**
     * Create maintenance restriction
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'maintenance',
            'reason' => $this->faker->randomElement([
                'Equipment maintenance',
                'System upgrade',
                'Facility cleaning',
                'Safety inspection',
                'Routine maintenance'
            ]),
        ]);
    }

    /**
     * Create meeting restriction
     */
    public function meeting(): static
    {
        return $this->state(function (array $attributes) {
            $startHour = $this->faker->numberBetween(9, 15);
            $duration = $this->faker->randomElement([1, 2, 3]); // 1-3 hours

            return [
                'type' => 'meeting',
                'reason' => $this->faker->randomElement([
                    'Team meeting',
                    'Client consultation',
                    'Training session',
                    'Board meeting',
                    'Project review'
                ]),
                'start_time' => sprintf('%02d:00', $startHour),
                'end_time' => sprintf('%02d:00', $startHour + $duration),
            ];
        });
    }

    /**
     * Create personal restriction
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'personal',
            'reason' => $this->faker->randomElement([
                'Personal appointment',
                'Family time',
                'Doctor appointment',
                'Personal day off',
                'Emergency'
            ]),
        ]);
    }

    /**
     * Create restriction for specific date range
     */
    public function forDateRange(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Create restriction for specific date
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $date,
            'end_date' => $date,
        ]);
    }

    /**
     * Create upcoming restriction (next 30 days)
     */
    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('now', '+30 days');
            return [
                'start_date' => $startDate->format('Y-m-d'),
            ];
        });
    }

    /**
     * Create past restriction
     */
    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $endDate = $this->faker->dateTimeBetween('-30 days', '-1 day');
            $startDate = $this->faker->dateTimeBetween('-60 days', $endDate);

            return [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];
        });
    }
}
