<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUserId = config('seeding.admin_user_id');

        // Create different types of bookings
        $this->createPastBookings($adminUserId);
        $this->createCurrentBookings($adminUserId);
        $this->createUpcomingBookings($adminUserId);
        $this->createSpecialBookings($adminUserId);
    }

    /**
     * Create past bookings for historical data
     */
    private function createPastBookings(int $userId): void
    {
        // Confirmed past bookings
        Booking::factory()
            ->count(20)
            ->past()
            ->confirmed()
            ->create(['user_id' => $userId]);

        // Some cancelled past bookings
        Booking::factory()
            ->count(3)
            ->past()
            ->cancelled()
            ->create(['user_id' => $userId]);

        // Some rejected past bookings
        Booking::factory()
            ->count(2)
            ->past()
            ->rejected()
            ->create(['user_id' => $userId]);

        $this->command->info('Created past bookings (25 total)');
    }

    /**
     * Create current/today bookings
     */
    private function createCurrentBookings(int $userId): void
    {
        $today = Carbon::today();

        // Today's confirmed bookings
        Booking::factory()
            ->count(2)
            ->today()
            ->confirmed()
            ->morning()
            ->create(['user_id' => $userId]);

        Booking::factory()
            ->today()
            ->confirmed()
            ->afternoon()
            ->create(['user_id' => $userId]);

        // Today's pending booking
        Booking::factory()
            ->today()
            ->pending()
            ->evening()
            ->create(['user_id' => $userId]);

        $this->command->info('Created today\'s bookings (4 total)');
    }

    /**
     * Create upcoming bookings
     */
    private function createUpcomingBookings(int $userId): void
    {
        // This week's confirmed bookings
        Booking::factory()
            ->count(8)
            ->upcoming()
            ->confirmed()
            ->create(['user_id' => $userId]);

        // Pending bookings awaiting approval
        Booking::factory()
            ->count(5)
            ->upcoming()
            ->pending()
            ->create(['user_id' => $userId]);

        // Some future bookings with Google Calendar sync
        Booking::factory()
            ->count(3)
            ->upcoming()
            ->confirmed()
            ->withGoogleCalendar()
            ->create(['user_id' => $userId]);

        $this->command->info('Created upcoming bookings (16 total)');
    }

    /**
     * Create special bookings with specific scenarios
     */
    private function createSpecialBookings(int $userId): void
    {
        $tomorrow = Carbon::tomorrow();
        $nextWeek = Carbon::today()->addWeek();

        // VIP client booking
        Booking::factory()
            ->confirmed()
            ->forDate($tomorrow->format('Y-m-d'))
            ->atTime('10:00', '12:00')
            ->forClient('John VIP Client', 'john.vip@example.com', '+1-555-0123')
            ->withNotes('VIP client - provide premium service and complimentary refreshments')
            ->withGoogleCalendar()
            ->create(['user_id' => $userId]);

        // Long consultation booking
        Booking::factory()
            ->confirmed()
            ->forDate($nextWeek->format('Y-m-d'))
            ->atTime('09:00', '12:00')
            ->forClient('Jane Long Session', 'jane.long@example.com', '+1-555-0456')
            ->withNotes('Extended consultation session - prepare comprehensive materials')
            ->create(['user_id' => $userId]);

        // Recurring client booking
        for ($i = 1; $i <= 4; $i++) {
            $weeklyDate = Carbon::today()->addWeeks($i)->addDays(2); // Every Wednesday

            Booking::factory()
                ->confirmed()
                ->forDate($weeklyDate->format('Y-m-d'))
                ->atTime('14:00', '15:00')
                ->forClient('Mike Recurring', 'mike.recurring@example.com', '+1-555-0789')
                ->withNotes('Weekly recurring appointment - session #' . $i)
                ->create(['user_id' => $userId]);
        }

        // Emergency booking (pending approval)
        Booking::factory()
            ->pending()
            ->forDate(Carbon::tomorrow()->format('Y-m-d'))
            ->atTime('16:00', '17:00')
            ->forClient('Sarah Emergency', 'sarah.emergency@example.com', '+1-555-0321')
            ->withNotes('URGENT: Emergency appointment request - please prioritize')
            ->create(['user_id' => $userId]);

        // Group booking
        Booking::factory()
            ->confirmed()
            ->forDate(Carbon::today()->addDays(10)->format('Y-m-d'))
            ->atTime('10:00', '13:00')
            ->forClient('Corporate Team Training', 'training@corporateclient.com', '+1-555-0654')
            ->withNotes('Group session for 8 people - prepare team materials and larger space')
            ->withGoogleCalendar()
            ->create(['user_id' => $userId]);

        $this->command->info('Created special scenario bookings (8 total)');
    }
}
