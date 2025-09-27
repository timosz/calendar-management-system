<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $bookingDate = $this->faker->dateTimeBetween('-30 days', '+60 days');

        // Generate realistic appointment times
        $startHour = $this->faker->numberBetween(8, 17);
        $startMinute = $this->faker->randomElement([0, 15, 30, 45]);
        $startTime = sprintf('%02d:%02d', $startHour, $startMinute);

        // Duration between 30 minutes and 3 hours
        $durationMinutes = $this->faker->randomElement([30, 45, 60, 90, 120, 180]);
        $endTime = Carbon::parse($startTime)->addMinutes($durationMinutes)->format('H:i');

        return [
            'user_id' => User::factory(),
            'client_name' => $this->faker->name(),
            'client_email' => $this->faker->safeEmail(),
            'client_phone' => $this->faker->optional(0.8)->phoneNumber(),
            'booking_date' => $bookingDate->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'rejected', 'cancelled']),
            'notes' => $this->faker->optional(0.6)->sentence(),
            'google_calendar_event_id' => $this->faker->optional(0.3)->uuid(),
        ];
    }

    /**
     * Create pending booking
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Create confirmed booking
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Create rejected booking
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Create cancelled booking
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Create booking for specific date
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => $date,
        ]);
    }

    /**
     * Create booking for today
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Create upcoming booking
     */
    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $date = $this->faker->dateTimeBetween('now', '+30 days');
            return [
                'booking_date' => $date->format('Y-m-d'),
            ];
        });
    }

    /**
     * Create past booking
     */
    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $date = $this->faker->dateTimeBetween('-30 days', '-1 day');
            return [
                'booking_date' => $date->format('Y-m-d'),
            ];
        });
    }

    /**
     * Create booking with specific time
     */
    public function atTime(string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Create short booking (30 minutes)
     */
    public function short(): static
    {
        return $this->state(function (array $attributes) {
            $startHour = $this->faker->numberBetween(9, 16);
            $startTime = sprintf('%02d:00', $startHour);
            $endTime = sprintf('%02d:30', $startHour);

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }

    /**
     * Create long booking (3+ hours)
     */
    public function long(): static
    {
        return $this->state(function (array $attributes) {
            $startHour = $this->faker->numberBetween(9, 14);
            $duration = $this->faker->numberBetween(180, 480); // 3-8 hours
            $endTime = Carbon::parse(sprintf('%02d:00', $startHour))->addMinutes($duration)->format('H:i');

            return [
                'start_time' => sprintf('%02d:00', $startHour),
                'end_time' => $endTime,
            ];
        });
    }

    /**
     * Create booking with Google Calendar sync
     */
    public function withGoogleCalendar(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_calendar_event_id' => $this->faker->uuid(),
        ]);
    }

    /**
     * Create booking without Google Calendar sync
     */
    public function withoutGoogleCalendar(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_calendar_event_id' => null,
        ]);
    }

    /**
     * Create booking with notes
     */
    public function withNotes(string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes ?? $this->faker->paragraph(),
        ]);
    }

    /**
     * Create booking without notes
     */
    public function withoutNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => null,
        ]);
    }

    /**
     * Create booking with specific client
     */
    public function forClient(string $name, string $email, string $phone = null): static
    {
        return $this->state(fn (array $attributes) => [
            'client_name' => $name,
            'client_email' => $email,
            'client_phone' => $phone,
        ]);
    }

    /**
     * Create morning booking (8 AM - 12 PM)
     */
    public function morning(): static
    {
        return $this->state(function (array $attributes) {
            $startHour = $this->faker->numberBetween(8, 11);
            $startTime = sprintf('%02d:00', $startHour);
            $endTime = Carbon::parse($startTime)->addHour()->format('H:i');

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }

    /**
     * Create afternoon booking (12 PM - 6 PM)
     */
    public function afternoon(): static
    {
        return $this->state(function (array $attributes) {
            $startHour = $this->faker->numberBetween(12, 17);
            $startTime = sprintf('%02d:00', $startHour);
            $endTime = Carbon::parse($startTime)->addHour()->format('H:i');

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }

    /**
     * Create evening booking (6 PM - 9 PM)
     */
    public function evening(): static
    {
        return $this->state(function (array $attributes) {
            $startHour = $this->faker->numberBetween(18, 20);
            $startTime = sprintf('%02d:00', $startHour);
            $endTime = Carbon::parse($startTime)->addHour()->format('H:i');

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }
}
