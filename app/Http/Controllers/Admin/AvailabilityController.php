<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Availability\BuildWeeklyScheduleAction;
use App\Actions\Availability\ToggleDayAvailabilityAction;
use App\Actions\Availability\UpdateWeeklyAvailabilityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAvailabilitiesRequest;
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
        protected BuildWeeklyScheduleAction $buildWeeklySchedule,
        protected UpdateWeeklyAvailabilityAction $updateWeeklyAvailability,
        protected ToggleDayAvailabilityAction $toggleDayAvailability
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Availabilities/Index', [
            'weeklySchedule' => $this->buildWeeklySchedule->execute(Auth::user()),
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
