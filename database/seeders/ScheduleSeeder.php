<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Zap\Facades\Zap;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // // Skip admin user for schedules
            // if ($user->email === 'admin@example.com') {
            //     continue;
            // }

            $this->createAvailabilities($user);
            $this->createBlockedSlots($user);
            $this->createAppointments($user);
        }
    }

    /**
     * Create availability schedules (working hours)
     */
    private function createAvailabilities(User $user): void
    {
        // Regular office hours - Monday to Friday
        Zap::for($user)
            ->named($user->name . ' - Office Hours')
            ->availability()
            ->from(Carbon::today())
            ->to(Carbon::today()->addMonths(6))
            ->addPeriod('09:00', '12:00') // Morning session
            ->addPeriod('17:00', '21:00') // Afternoon session
            ->weekly(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
            ->withMetadata([
                'location' => 'Main Office',
                'type' => 'regular_hours'
            ])
            ->save();

        // Weekend availability (limited hours)
        Zap::for($user)
            ->named($user->name . ' - Weekend Hours')
            ->availability()
            ->from(Carbon::today())
            ->to(Carbon::today()->addMonths(6))
            ->addPeriod('10:00', '14:00')
            ->weekly(['saturday'])
            ->withMetadata([
                'location' => 'Main Office',
                'type' => 'weekend_hours'
            ])
            ->save();
    }

    /**
     * Create blocked time slots (unavailable periods)
     */
    private function createBlockedSlots(User $user): void
    {
        // Daily lunch break
        Zap::for($user)
            ->named('Lunch Break')
            ->blocked()
            ->from(Carbon::today())
            ->to(Carbon::today()->addMonths(6))
            ->addPeriod('12:00', '13:00')
            ->weekly(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
            ->withMetadata([
                'type' => 'lunch_break',
                'recurring' => true
            ])
            ->save();

        // Holiday period
        $holidayStart = Carbon::today()->addWeeks(8);
        $holidayEnd = $holidayStart->copy()->addDays(6);

        Zap::for($user)
            ->named('Holiday Leave')
            ->blocked()
            ->from($holidayStart)
            ->to($holidayEnd)
            ->addPeriod('00:00', '23:59')
            ->withMetadata([
                'type' => 'vacation',
                'reason' => 'Annual leave'
            ])
            ->save();

        // Random maintenance/meeting blocks
        for ($i = 1; $i <= 3; $i++) {
            $blockDate = Carbon::today()->addWeeks($i * 2);

            Zap::for($user)
                ->named('Maintenance/Meeting Block')
                ->blocked()
                ->from($blockDate)
                ->addPeriod('15:00', '16:00')
                ->withMetadata([
                    'type' => 'maintenance',
                    'reason' => 'System maintenance'
                ])
                ->save();
        }
    }

    /**
     * Create sample appointments
     */
    private function createAppointments(User $user): void
    {
        // Create appointments for the next few weeks
        for ($week = 1; $week <= 4; $week++) {
            for ($day = 0; $day < 3; $day++) { // 3 appointments per week
                $appointmentDate = Carbon::today()
                    ->addWeeks($week)
                    ->startOfWeek()
                    ->addDays($day + 1); // Skip Sunday

                // Skip weekends
                if ($appointmentDate->isWeekend()) {
                    continue;
                }

                $startTimes = ['09:30', '10:30', '11:00', '14:30', '15:30', '16:00'];
                $startTime = $startTimes[array_rand($startTimes)];
                $endTime = Carbon::parse($startTime)->addMinutes(60)->format('H:i');

                $appointmentTypes = [
                    'consultation' => 'Patient Consultation',
                    'meeting' => 'Team Meeting',
                    'conference' => 'Conference Call',
                    'presentation' => 'Client Presentation'
                ];

                $type = array_rand($appointmentTypes);
                $title = $appointmentTypes[$type];

                try {
                    Zap::for($user)
                        ->named($title . ' - ' . $appointmentDate->format('M d'))
                        ->appointment()
                        ->from($appointmentDate)
                        ->addPeriod($startTime, $endTime)
                        ->withMetadata([
                            'type' => $type,
                            'client_name' => $this->generateClientName(),
                            'priority' => $this->getRandomPriority(),
                            'location' => str_contains($user->name, 'Room') ? $user->name : 'Office',
                            'contact_email' => $this->generateEmail(),
                            'notes' => 'Automatically generated appointment'
                        ])
                        ->save();
                } catch (\Exception $e) {
                    // Skip if there's a conflict (overlap with existing appointment)
                    continue;
                }
            }
        }
    }

    /**
     * Generate random client name
     */
    private function generateClientName(): string
    {
        $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'Robert', 'Lisa', 'James', 'Maria'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    /**
     * Generate random priority
     */
    private function getRandomPriority(): string
    {
        $priorities = ['low', 'medium', 'high'];
        return $priorities[array_rand($priorities)];
    }

    /**
     * Generate random email
     */
    private function generateEmail(): string
    {
        $names = ['john', 'jane', 'michael', 'sarah', 'david', 'emma'];
        $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'example.com'];

        return $names[array_rand($names)] . rand(1, 999) . '@' . $domains[array_rand($domains)];
    }
}
