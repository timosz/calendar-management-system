<?php

namespace Database\Seeders;

use App\Models\Availability;
use Illuminate\Database\Seeder;

class AvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUserId = config('seeding.admin_user_id');

        // Create typical business schedule scenarios
        $this->createBusinessSchedule($adminUserId);

        // Uncomment one of these to try different scenarios:
        // $this->createServiceProviderSchedule($adminUserId);
        // $this->createHotelSchedule($adminUserId);
        // $this->createFlexibleSchedule($adminUserId);
    }

    /**
     * Create standard business hours (Monday-Friday, 9 AM - 5 PM)
     */
    private function createBusinessSchedule(int $userId): void
    {
        // Monday to Friday: 9 AM - 5 PM
        for ($day = 1; $day <= 5; $day++) {
            Availability::factory()
                ->forDay($day)
                ->businessHours()
                ->create(['user_id' => $userId]);
        }

        $this->command->info('Created standard business hours (Mon-Fri, 9 AM - 5 PM)');
    }

    /**
     * Create service provider schedule (Monday-Saturday with varied hours)
     */
    private function createServiceProviderSchedule(int $userId): void
    {
        // Monday: 9 AM - 5 PM
        Availability::factory()
            ->forDay(1)
            ->businessHours()
            ->create(['user_id' => $userId]);

        // Tuesday: 8 AM - 6 PM
        Availability::factory()
            ->forDay(2)
            ->create([
                'user_id' => $userId,
                'start_time' => '08:00',
                'end_time' => '18:00',
            ]);

        // Wednesday: 10 AM - 7 PM
        Availability::factory()
            ->forDay(3)
            ->create([
                'user_id' => $userId,
                'start_time' => '10:00',
                'end_time' => '19:00',
            ]);

        // Thursday: 9 AM - 5 PM
        Availability::factory()
            ->forDay(4)
            ->businessHours()
            ->create(['user_id' => $userId]);

        // Friday: 8 AM - 4 PM
        Availability::factory()
            ->forDay(5)
            ->create([
                'user_id' => $userId,
                'start_time' => '08:00',
                'end_time' => '16:00',
            ]);

        // Saturday: 9 AM - 1 PM
        Availability::factory()
            ->forDay(6)
            ->morningOnly()
            ->create([
                'user_id' => $userId,
                'end_time' => '13:00',
            ]);

        $this->command->info('Created service provider schedule (Mon-Sat with varied hours)');
    }

    /**
     * Create hotel check-in schedule (daily 3 PM - 11:59 PM)
     */
    private function createHotelSchedule(int $userId): void
    {
        // Every day: 3 PM - 11:59 PM (check-in hours)
        for ($day = 0; $day <= 6; $day++) {
            Availability::factory()
                ->forDay($day)
                ->hotelCheckIn()
                ->create(['user_id' => $userId]);
        }

        $this->command->info('Created hotel schedule (Daily, 3 PM - 11:59 PM)');
    }

    /**
     * Create flexible schedule with some inactive days
     */
    private function createFlexibleSchedule(int $userId): void
    {
        // Monday: Extended hours
        Availability::factory()
            ->forDay(1)
            ->extendedHours()
            ->create(['user_id' => $userId]);

        // Tuesday: Standard hours
        Availability::factory()
            ->forDay(2)
            ->businessHours()
            ->create(['user_id' => $userId]);

        // Wednesday: Inactive (day off)
        Availability::factory()
            ->forDay(3)
            ->businessHours()
            ->inactive()
            ->create(['user_id' => $userId]);

        // Thursday: Afternoon only
        Availability::factory()
            ->forDay(4)
            ->afternoonOnly()
            ->create(['user_id' => $userId]);

        // Friday: Morning only
        Availability::factory()
            ->forDay(5)
            ->morningOnly()
            ->create(['user_id' => $userId]);

        // Saturday: Extended hours
        Availability::factory()
            ->forDay(6)
            ->create([
                'user_id' => $userId,
                'start_time' => '08:00',
                'end_time' => '20:00',
            ]);

        $this->command->info('Created flexible schedule with varied hours and one inactive day');
    }
}
