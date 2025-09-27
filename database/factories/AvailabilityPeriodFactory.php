<?php

namespace Database\Factories;

use App\Models\AvailabilityPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityPeriod>
 */
class AvailabilityPeriodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = AvailabilityPeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(8, 16);
        $endHour = $this->faker->numberBetween($startHour + 1, 18);

        return [
            'user_id' => User::factory(),
            'day_of_week' => $this->faker->numberBetween(1, 5), // Monday to Friday by default
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $endHour),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the availability period is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the availability period for a specific day.
     */
    public function forDay(int $dayOfWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayOfWeek,
        ]);
    }

    /**
     * Set morning hours (9 AM - 12 PM).
     */
    public function morningHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);
    }

    /**
     * Set afternoon hours (1 PM - 5 PM).
     */
    public function afternoonHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
        ]);
    }

    /**
     * Set full day hours (9 AM - 5 PM).
     */
    public function fullDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
    }

    /**
     * Create a standard weekday schedule (Monday to Friday).
     */
    public function weekdaySchedule(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $this->faker->numberBetween(1, 5),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
    }
}
