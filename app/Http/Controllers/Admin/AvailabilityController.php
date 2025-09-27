<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AvailabilityController extends Controller
{
    public function index(): Response
    {
        $availabilities = Auth::user()
            ->availabilities()
            ->orderBy('day_of_week')
            ->get()
            ->map(function ($availability) {
                return [
                    'id' => $availability->id,
                    'day_of_week' => $availability->day_of_week,
                    'day_name' => $availability->day_name,
                    'start_time' => $availability->start_time->format('H:i'),
                    'end_time' => $availability->end_time->format('H:i'),
                    'is_active' => $availability->is_active,
                    'duration_minutes' => $availability->getDurationInMinutes(),
                ];
            });

        return Inertia::render('Admin/Availabilities/Index', [
            'availabilities' => $availabilities,
            'dayNames' => Availability::getDayNames(),
        ]);
    }

    public function create(): Response
    {
        // Get days that don't have availability yet
        $existingDays = Auth::user()
            ->availabilities()
            ->pluck('day_of_week')
            ->toArray();

        $availableDays = collect(Availability::getDayNames())
            ->filter(function ($dayName, $dayNumber) use ($existingDays) {
                return !in_array($dayNumber, $existingDays);
            });

        return Inertia::render('Admin/Availabilities/Create', [
            'availableDays' => $availableDays,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => [
                'required',
                'integer',
                'between:0,6',
                Rule::unique('availabilities')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'boolean',
        ]);

        Auth::user()->availabilities()->create($validated);

        return redirect()
            ->route('admin.availabilities.index')
            ->with('success', 'Availability created successfully.');
    }

    public function show(Availability $availability): Response
    {
        return Inertia::render('Admin/Availabilities/Show', [
            'availability' => [
                'id' => $availability->id,
                'day_of_week' => $availability->day_of_week,
                'day_name' => $availability->day_name,
                'start_time' => $availability->start_time->format('H:i'),
                'end_time' => $availability->end_time->format('H:i'),
                'is_active' => $availability->is_active,
                'duration_minutes' => $availability->getDurationInMinutes(),
                'created_at' => $availability->created_at,
                'updated_at' => $availability->updated_at,
            ],
        ]);
    }

    public function edit(Availability $availability): Response
    {
        return Inertia::render('Admin/Availabilities/Edit', [
            'availability' => [
                'id' => $availability->id,
                'day_of_week' => $availability->day_of_week,
                'day_name' => $availability->day_name,
                'start_time' => $availability->start_time->format('H:i'),
                'end_time' => $availability->end_time->format('H:i'),
                'is_active' => $availability->is_active,
            ],
            'dayNames' => Availability::getDayNames(),
        ]);
    }

    public function update(Request $request, Availability $availability): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => [
                'required',
                'integer',
                'between:0,6',
                Rule::unique('availabilities')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                })->ignore($availability->id),
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'boolean',
        ]);

        $availability->update($validated);

        return redirect()
            ->route('admin.availabilities.index')
            ->with('success', 'Availability updated successfully.');
    }

    public function destroy(Availability $availability): RedirectResponse
    {
        $availability->delete();

        return redirect()
            ->route('admin.availabilities.index')
            ->with('success', 'Availability deleted successfully.');
    }

    /**
     * Toggle active status of availability
     */
    public function toggleActive(Availability $availability): RedirectResponse
    {
        $availability->update([
            'is_active' => !$availability->is_active,
        ]);

        $status = $availability->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('admin.availabilities.index')
            ->with('success', "Availability {$status} successfully.");
    }
}
