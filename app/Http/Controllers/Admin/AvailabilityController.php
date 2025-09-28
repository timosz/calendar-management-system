<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AvailabilityController extends Controller
{
    public function index(): Response
    {
        // Get all availabilities for the authenticated user, indexed by day_of_week
        $availabilities = Auth::user()
            ->availabilities()
            ->get()
            ->keyBy('day_of_week');

        // Prepare the weekly schedule data
        $weeklySchedule = [];
        $dayNames = Availability::getDayNames();

        // Start from Monday (1) and include Sunday (0) at the end
        $dayOrder = [1, 2, 3, 4, 5, 6, 0];

        foreach ($dayOrder as $dayNumber) {
            $availability = $availabilities->get($dayNumber);

            $weeklySchedule[] = [
                'day_of_week' => $dayNumber,
                'day_name' => $dayNames[$dayNumber],
                'is_active' => $availability ? $availability->is_active : false,
                'start_time' => $availability ? $availability->start_time->format('H:i') : null,
                'end_time' => $availability ? $availability->end_time->format('H:i') : null,
                'id' => $availability ? $availability->id : null,
            ];
        }

        return Inertia::render('Admin/Availabilities/Index', [
            'weeklySchedule' => $weeklySchedule,
            'timeSlots' => $this->generateTimeSlots(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'availabilities' => 'required|array',
            'availabilities.*.day_of_week' => 'required|integer|between:0,6',
            'availabilities.*.is_active' => 'required|boolean',
            'availabilities.*.start_time' => 'nullable|date_format:H:i|required_if:availabilities.*.is_active,true',
            'availabilities.*.end_time' => 'nullable|date_format:H:i|required_if:availabilities.*.is_active,true|after:availabilities.*.start_time',
        ], [
            'availabilities.*.start_time.required_if' => 'Start time is required when day is active.',
            'availabilities.*.end_time.required_if' => 'End time is required when day is active.',
            'availabilities.*.end_time.after' => 'End time must be after start time.',
        ]);

        DB::transaction(function () use ($validated) {
            $userId = Auth::id();

            foreach ($validated['availabilities'] as $availabilityData) {
                $dayOfWeek = $availabilityData['day_of_week'];

                // Find existing availability for this day
                $availability = Availability::where('user_id', $userId)
                    ->where('day_of_week', $dayOfWeek)
                    ->first();

                if ($availabilityData['is_active']) {
                    // Create or update availability
                    $data = [
                        'user_id' => $userId,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $availabilityData['start_time'],
                        'end_time' => $availabilityData['end_time'],
                        'is_active' => true,
                    ];

                    if ($availability) {
                        $availability->update($data);
                    } else {
                        Availability::create($data);
                    }
                } else {
                    // If not active, delete the availability if it exists
                    if ($availability) {
                        $availability->delete();
                    }
                }
            }
        });

        return redirect()
            ->route('admin.availabilities.index')
            ->with('success', 'Weekly availability updated successfully.');
    }

    /**
     * Generate time slots in 15-minute intervals
     */
    private function generateTimeSlots(): array
    {
        $timeSlots = [];

        for ($hour = 0; $hour < 24; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 15) {
                $time = sprintf('%02d:%02d', $hour, $minute);
                $timeSlots[] = [
                    'value' => $time,
                    'label' => $time,
                ];
            }
        }

        return $timeSlots;
    }

    /**
     * Toggle active status for a specific day
     */
    public function toggleDay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
        ]);

        $availability = Auth::user()
            ->availabilities()
            ->where('day_of_week', $validated['day_of_week'])
            ->first();

        if ($availability) {
            $availability->update([
                'is_active' => !$availability->is_active,
            ]);

            $status = $availability->is_active ? 'activated' : 'deactivated';
            $dayName = $availability->day_name;

            return redirect()
                ->route('admin.availabilities.index')
                ->with('success', "{$dayName} availability {$status} successfully.");
        }

        return redirect()
            ->route('admin.availabilities.index')
            ->with('error', 'No availability found for this day.');
    }
}
