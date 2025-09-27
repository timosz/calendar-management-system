<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bookingDate = $this->faker->dateTimeBetween('now', '+30 days');
        $startHour = $this->faker->numberBetween(9, 16);
        $duration = $this->faker->randomElement([30, 60, 90, 120]); // minutes
        $endTime = (clone $bookingDate)->setTime($startHour, 0)->addMinutes($duration);

        return [
            'user_id' => User::factory(),
            'client_name' => $this->faker->name(),
            'client_email' => $this->faker->safeEmail(),
            'client_phone' => $this->faker->optional(0.8)->phoneNumber(),
            'booking_date' => $bookingDate->format('Y-m-d'),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => $endTime->format('H:i:s'),
            'status' => $this->faker->randomElement(Booking::getStatuses()),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'google_calendar_event_id' => null,
        ];
    }

    /**
     * Indicate that the booking is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the booking is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_CONFIRMED,
        ]);
    }

    /**
     * Indicate that the booking is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_REJECTED,
        ]);
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_CANCELLED,
        ]);
    }

    /**
     * Set the booking for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Set the booking for tomorrow.
     */
    public function tomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => now()->addDay()->format('Y-m-d'),
        ]);
    }

    /**
     * Set the booking for a specific date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => $date,
        ]);
    }

    /**
     * Set morning appointment (9 AM - 12 PM).
     */
    public function morningAppointment(): static
    {
        $startHour = $this->faker->numberBetween(9, 11);
        $duration = $this->faker->randomElement([30, 60, 90]);

        return $this->state(function (array $attributes) use ($startHour, $duration) {
            $startTime = sprintf('%02d:00:00', $startHour);
            $endDateTime = \Carbon\Carbon::createFromFormat('H:i:s', $startTime)->addMinutes($duration);

            return [
                'start_time' => $startTime,
                'end_time' => $endDateTime->format('H:i:s'),
            ];
        });
    }

    /**
     * Set afternoon appointment (1 PM - 5 PM).
     */
    public function afternoonAppointment(): static
    {
        $startHour = $this->faker->numberBetween(13, 16);
        $duration = $this->faker->randomElement([30, 60, 90]);

        return $this->state(function (array $attributes) use ($startHour, $duration) {
            $startTime = sprintf('%02d:00:00', $startHour);
            $endDateTime = \Carbon\Carbon::createFromFormat('H:i:s', $startTime)->addMinutes($duration);

            return [
                'start_time' => $startTime,
                'end_time' => $endDateTime->format('H:i:s'),
            ];
        });
    }

    /**
     * Set specific time range.
     */
    public function timeRange(string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Add notes to the booking.
     */
    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }

    /**
     * Add Google Calendar event ID.
     */
    public function withGoogleCalendarEvent(string $eventId): static
    {
        return $this->state(fn (array $attributes) => [
            'google_calendar_event_id' => $eventId,
        ]);
    }
}
