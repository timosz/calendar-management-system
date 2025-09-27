<?php

namespace Database\Seeders;

use App\Models\Restriction;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RestrictionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUserId = config('seeding.admin_user_id');

        // Create various types of restrictions
        $this->createHolidays($adminUserId);
        $this->createBreaks($adminUserId);
        $this->createMeetings($adminUserId);
        $this->createMaintenance($adminUserId);
        $this->createPersonalTime($adminUserId);
    }

    /**
     * Create holiday restrictions
     */
    private function createHolidays(int $userId): void
    {
        // Christmas break (multi-day)
        Restriction::factory()
            ->holiday('Christmas Break')
            ->forDateRange('2025-12-24', '2025-12-26')
            ->create(['user_id' => $userId]);

        // New Year's Day
        Restriction::factory()
            ->holiday('New Year\'s Day')
            ->forDate('2025-01-01')
            ->create(['user_id' => $userId]);

        // Summer vacation (week-long)
        $vacationStart = Carbon::now()->addMonths(3)->startOfWeek();
        Restriction::factory()
            ->holiday('Summer Vacation')
            ->forDateRange(
                $vacationStart->format('Y-m-d'),
                $vacationStart->copy()->addDays(6)->format('Y-m-d')
            )
            ->create(['user_id' => $userId]);

        $this->command->info('Created holiday restrictions');
    }

    /**
     * Create break restrictions
     */
    private function createBreaks(int $userId): void
    {
        // Daily lunch breaks for the next 30 days
        $today = Carbon::today();
        for ($i = 0; $i < 30; $i++) {
            $date = $today->copy()->addDays($i);

            // Only add lunch breaks on weekdays
            if ($date->isWeekday()) {
                Restriction::factory()
                    ->lunchBreak()
                    ->forDate($date->format('Y-m-d'))
                    ->create(['user_id' => $userId]);
            }
        }

        // Coffee breaks (some random days)
        for ($i = 0; $i < 10; $i++) {
            $date = Carbon::today()->addDays(rand(1, 30));

            Restriction::factory()
                ->partialDay('10:15', '10:30')
                ->forDate($date->format('Y-m-d'))
                ->create([
                    'user_id' => $userId,
                    'type' => 'break',
                    'reason' => 'Coffee break',
                ]);
        }

        $this->command->info('Created break restrictions (lunch breaks + coffee breaks)');
    }

    /**
     * Create meeting restrictions
     */
    private function createMeetings(int $userId): void
    {
        // Weekly team meeting (next 8 weeks)
        for ($week = 1; $week <= 8; $week++) {
            $meetingDate = Carbon::today()->addWeeks($week)->startOfWeek()->addDays(1); // Every Tuesday

            Restriction::factory()
                ->meeting()
                ->forDate($meetingDate->format('Y-m-d'))
                ->create([
                    'user_id' => $userId,
                    'start_time' => '10:00',
                    'end_time' => '11:00',
                    'reason' => 'Weekly team meeting',
                ]);
        }

        // Some random client meetings
        for ($i = 0; $i < 5; $i++) {
            $meetingDate = Carbon::today()->addDays(rand(1, 60));

            Restriction::factory()
                ->meeting()
                ->forDate($meetingDate->format('Y-m-d'))
                ->create(['user_id' => $userId]);
        }

        $this->command->info('Created meeting restrictions');
    }

    /**
     * Create maintenance restrictions
     */
    private function createMaintenance(int $userId): void
    {
        // Quarterly system maintenance
        $maintenanceDate = Carbon::today()->addMonths(2)->startOfMonth();

        Restriction::factory()
            ->maintenance()
            ->forDate($maintenanceDate->format('Y-m-d'))
            ->create([
                'user_id' => $userId,
                'start_time' => '02:00',
                'end_time' => '06:00',
                'reason' => 'Quarterly system maintenance',
            ]);

        // Equipment maintenance (random day)
        $equipmentMaintenanceDate = Carbon::today()->addDays(rand(15, 45));

        Restriction::factory()
            ->maintenance()
            ->forDate($equipmentMaintenanceDate->format('Y-m-d'))
            ->create([
                'user_id' => $userId,
                'start_time' => '14:00',
                'end_time' => '16:00',
                'reason' => 'Equipment maintenance',
            ]);

        $this->command->info('Created maintenance restrictions');
    }

    /**
     * Create personal time restrictions
     */
    private function createPersonalTime(int $userId): void
    {
        // Doctor appointment
        $appointmentDate = Carbon::today()->addDays(rand(7, 21));

        Restriction::factory()
            ->personal()
            ->forDate($appointmentDate->format('Y-m-d'))
            ->create([
                'user_id' => $userId,
                'start_time' => '14:30',
                'end_time' => '16:00',
                'reason' => 'Doctor appointment',
            ]);

        // Personal day off
        $personalDay = Carbon::today()->addDays(rand(30, 60));

        Restriction::factory()
            ->personal()
            ->forDate($personalDay->format('Y-m-d'))
            ->create([
                'user_id' => $userId,
                'reason' => 'Personal day off',
            ]);

        $this->command->info('Created personal time restrictions');
    }
}
