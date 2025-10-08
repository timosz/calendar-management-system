<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Availability\ToggleDayAvailabilityAction;
use App\Actions\Availability\UpdateWeeklyAvailabilityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAvailabilitiesRequest;
use App\Models\Availability;
use App\Services\TimeSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function __construct(
        protected TimeSlotService $timeSlotService,
        protected UpdateWeeklyAvailabilityAction $updateWeeklyAvailability,
        protected ToggleDayAvailabilityAction $toggleDayAvailability
    ) {
    }

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
                'start_time' => $availability ? $availability->start_time : null,
                'end_time' => $availability ? $availability->end_time : null,
                'id' => $availability ? $availability->id : null,
            ];
        }

        return Inertia::render('Admin/Availabilities/Index', [
            'weeklySchedule' => $weeklySchedule,
            'timeSlots' => $this->timeSlotService->generateTimeOptions(15),
        ]);
    }

    public function update(UpdateAvailabilitiesRequest $request): RedirectResponse
    {
        $this->updateWeeklyAvailability->execute(
            Auth::id(),
            $request->validated()['availabilities']
        );

        return redirect()
            ->route('admin.availabilities.index')
            ->with('success', 'Weekly availability updated successfully.');
    }

    /**
     * Toggle active status for a specific day
     */
    public function toggleDay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
        ]);

        $result = $this->toggleDayAvailability->execute(
            Auth::id(),
            $validated['day_of_week']
        );

        $flashType = $result['success'] ? 'success' : 'error';

        return redirect()
            ->route('admin.availabilities.index')
            ->with($flashType, $result['message']);
    }
}
