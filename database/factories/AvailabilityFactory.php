<?php

namespace Database\Factories;

use App\Models\Availability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Availability>
 */
class AvailabilityFactory extends Factory
{
    protected $model = Availability::class;

    public function definition(): array
    {
        // Generate realistic working hours
        $startHour = $this->faker->numberBetween(6, 10); // Start between 6 AM and 10 AM
        $endHour = $this->faker->numberBetween(15, 20);  // End between 3 PM and 8 PM

        return [
            'user_id' => User::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $endHour),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Create availability for a specific day of the week
     */
    public function forDay(int $dayOfWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayOfWeek,
        ]);
    }

    /**
     * Create typical business hours (9 AM - 5 PM)
     */
    public function businessHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    }

    /**
     * Create extended hours (8 AM - 8 PM)
     */
    public function extendedHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '08:00',
            'end_time' => '20:00',
        ]);
    }

    /**
     * Create morning hours only
     */
    public function morningOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);
    }

    /**
     * Create afternoon/evening hours only
     */
    public function afternoonOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '13:00',
            'end_time' => '18:00',
        ]);
    }

    /**
     * Create hotel check-in hours (3 PM - 11:59 PM)
     */
    public function hotelCheckIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '15:00',
            'end_time' => '23:59',
        ]);
    }

    /**
     * Create inactive availability
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create active availability
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create a full work week (Monday-Friday)
     */
    public function workWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $this->faker->numberBetween(1, 5), // Monday to Friday
        ]);
    }

    /**
     * Create weekend availability
     */
    public function weekend(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $this->faker->randomElement([0, 6]), // Sunday or Saturday
        ]);
    }
}
