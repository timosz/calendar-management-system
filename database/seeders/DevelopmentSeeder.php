<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Availability;
use App\Models\Restriction;
use App\Models\Booking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DevelopmentSeeder extends Seeder
{
    /**
     * Quick seeder for development with minimal but useful data
     */
    public function run(): void
    {
        // Create admin user
        $user = User::factory()->create([
            'name' => 'Dev Admin',
            'email' => 'dev@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create simple weekday schedule
        for ($day = 1; $day <= 5; $day++) {
            Availability::factory()
                ->forDay($day)
                ->businessHours()
                ->create(['user_id' => $user->id]);
        }

        // Add lunch breaks
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today()->addDays($i);
            if ($date->isWeekday()) {
                Restriction::factory()
                    ->lunchBreak()
                    ->forDate($date->format('Y-m-d'))
                    ->create(['user_id' => $user->id]);
            }
        }

        // Create some bookings
        Booking::factory()->count(3)->confirmed()->today()->create(['user_id' => $user->id]);
        Booking::factory()->count(2)->pending()->upcoming()->create(['user_id' => $user->id]);
        Booking::factory()->count(5)->confirmed()->past()->create(['user_id' => $user->id]);

        $this->command->info('Development data created:');
        $this->command->info('- Admin user: dev@example.com (password: password)');
        $this->command->info('- Business hours: Mon-Fri 9 AM - 5 PM');
        $this->command->info('- Daily lunch breaks: 12:00 - 13:00');
        $this->command->info('- Sample bookings: 10 total');
    }
}
