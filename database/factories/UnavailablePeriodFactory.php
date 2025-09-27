<?php

namespace Database\Factories;

use App\Models\UnavailablePeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnavailablePeriod>
 */
class UnavailablePeriodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = UnavailablePeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+60 days');
        $endDate = (clone $startDate)->modify('+' . $this->faker->numberBetween(0, 7) . ' days');

        return [
            'user_id' => User::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => null, // All day by default
            'end_time' => null,   // All day by default
            'reason' => $this->faker->optional(0.7)->randomElement([
                'Vacation',
                'Sick Leave',
                'Personal Day',
                'Conference',
                'Holiday',
                'Training',
                'Family Emergency',
            ]),
        ];
    }

    /**
     * Create an all-day unavailable period.
     */
    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => null,
            'end_time' => null,
        ]);
    }

    /**
     * Create a partial day unavailable period.
     */
    public function partialDay(string $startTime = null, string $endTime = null): static
    {
        $start = $startTime ?? sprintf('%02d:00:00', $this->faker->numberBetween(9, 14));
        $end = $endTime ?? sprintf('%02d:00:00', $this->faker->numberBetween(15, 17));

        return $this->state(fn (array $attributes) => [
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    /**
     * Create a single day unavailable period.
     */
    public function singleDay(string $date = null): static
    {
        $targetDate = $date ?? $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d');

        return $this->state(fn (array $attributes) => [
            'start_date' => $targetDate,
            'end_date' => $targetDate,
        ]);
    }

    /**
     * Create a weekend unavailable period.
     */
    public function weekend(): static
    {
        $saturday = $this->faker->dateTimeBetween('now', '+30 days');
        // Find next Saturday
        while ($saturday->format('w') != 6) {
            $saturday->modify('+1 day');
        }
        $sunday = (clone $saturday)->modify('+1 day');

        return $this->state(fn (array $attributes) => [
            'start_date' => $saturday->format('Y-m-d'),
            'end_date' => $sunday->format('Y-m-d'),
            'reason' => 'Weekend',
        ]);
    }

    /**
     * Create a vacation period.
     */
    public function vacation(int $days = null): static
    {
        $duration = $days ?? $this->faker->numberBetween(3, 14);
        $startDate = $this->faker->dateTimeBetween('+1 week', '+8 weeks');
        $endDate = (clone $startDate)->modify('+' . ($duration - 1) . ' days');

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'reason' => 'Vacation',
        ]);
    }

    /**
     * Create a holiday period.
     */
    public function holiday(string $holidayName = null): static
    {
        $holidays = [
            'New Year\'s Day',
            'Independence Day',
            'Christmas Day',
            'Thanksgiving',
            'Labor Day',
            'Memorial Day',
        ];

        return $this->state(fn (array $attributes) => [
            'reason' => $holidayName ?? $this->faker->randomElement($holidays),
        ]);
    }

    /**
     * Create a conference/training period.
     */
    public function conference(): static
    {
        $duration = $this->faker->numberBetween(1, 3);
        $startDate = $this->faker->dateTimeBetween('+2 weeks', '+12 weeks');
        $endDate = (clone $startDate)->modify('+' . ($duration - 1) . ' days');

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'reason' => $this->faker->randomElement(['Conference', 'Training', 'Workshop']),
        ]);
    }

    /**
     * Create a sick leave period.
     */
    public function sickLeave(): static
    {
        $duration = $this->faker->numberBetween(1, 5);
        $startDate = $this->faker->dateTimeBetween('now', '+2 weeks');
        $endDate = (clone $startDate)->modify('+' . ($duration - 1) . ' days');

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'reason' => 'Sick Leave',
        ]);
    }

    /**
     * Create for a specific date range.
     */
    public function dateRange(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Add a specific reason.
     */
    public function withReason(string $reason): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => $reason,
        ]);
    }

    /**
     * Create for today.
     */
    public function today(): static
    {
        $today = now()->format('Y-m-d');
        return $this->state(fn (array $attributes) => [
            'start_date' => $today,
            'end_date' => $today,
        ]);
    }

    /**
     * Create for tomorrow.
     */
    public function tomorrow(): static
    {
        $tomorrow = now()->addDay()->format('Y-m-d');
        return $this->state(fn (array $attributes) => [
            'start_date' => $tomorrow,
            'end_date' => $tomorrow,
        ]);
    }
}
