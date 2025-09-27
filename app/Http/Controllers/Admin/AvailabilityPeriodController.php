<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AvailabilityPeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $availabilityPeriods = AvailabilityPeriod::query()
            ->forUser(auth()->id())
            ->with('user')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return Inertia::render('Admin/AvailabilityPeriods/Index', [
            'availabilityPeriods' => $availabilityPeriods,
            'dayNames' => [
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/AvailabilityPeriods/Create', [
            'dayOptions' => [
                ['value' => 1, 'label' => 'Monday'],
                ['value' => 2, 'label' => 'Tuesday'],
                ['value' => 3, 'label' => 'Wednesday'],
                ['value' => 4, 'label' => 'Thursday'],
                ['value' => 5, 'label' => 'Friday'],
                ['value' => 6, 'label' => 'Saturday'],
                ['value' => 0, 'label' => 'Sunday'],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'boolean',
        ]);

        // Check for overlapping periods on the same day
        $overlapping = AvailabilityPeriod::forUser(auth()->id())
            ->forDay($validated['day_of_week'])
            ->active()
            ->get()
            ->filter(function ($period) use ($validated) {
                return $period->overlaps($validated['start_time'] . ':00', $validated['end_time'] . ':00');
            });

        if ($overlapping->isNotEmpty()) {
            return back()->withErrors([
                'start_time' => 'This time period overlaps with an existing availability period.',
            ]);
        }

        AvailabilityPeriod::create([
            'user_id' => auth()->id(),
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.availability-periods.index')
            ->with('success', 'Availability period created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AvailabilityPeriod $availabilityPeriod): Response
    {
        return Inertia::render('Admin/AvailabilityPeriods/Show', [
            'availabilityPeriod' => $availabilityPeriod->load('user'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AvailabilityPeriod $availabilityPeriod): Response
    {
        return Inertia::render('Admin/AvailabilityPeriods/Edit', [
            'availabilityPeriod' => $availabilityPeriod,
            'dayOptions' => [
                ['value' => 1, 'label' => 'Monday'],
                ['value' => 2, 'label' => 'Tuesday'],
                ['value' => 3, 'label' => 'Wednesday'],
                ['value' => 4, 'label' => 'Thursday'],
                ['value' => 5, 'label' => 'Friday'],
                ['value' => 6, 'label' => 'Saturday'],
                ['value' => 0, 'label' => 'Sunday'],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AvailabilityPeriod $availabilityPeriod): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'boolean',
        ]);

        // Check for overlapping periods on the same day (excluding current period)
        $overlapping = AvailabilityPeriod::forUser(auth()->id())
            ->forDay($validated['day_of_week'])
            ->active()
            ->where('id', '!=', $availabilityPeriod->id)
            ->get()
            ->filter(function ($period) use ($validated) {
                return $period->overlaps($validated['start_time'] . ':00', $validated['end_time'] . ':00');
            });

        if ($overlapping->isNotEmpty()) {
            return back()->withErrors([
                'start_time' => 'This time period overlaps with an existing availability period.',
            ]);
        }

        $availabilityPeriod->update([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.availability-periods.index')
            ->with('success', 'Availability period updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AvailabilityPeriod $availabilityPeriod): RedirectResponse
    {
        $availabilityPeriod->delete();

        return redirect()->route('admin.availability-periods.index')
            ->with('success', 'Availability period deleted successfully.');
    }

    /**
     * Toggle the active status of the specified resource.
     */
    public function toggle(AvailabilityPeriod $availabilityPeriod): RedirectResponse
    {
        $availabilityPeriod->update([
            'is_active' => !$availabilityPeriod->is_active,
        ]);

        $status = $availabilityPeriod->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Availability period {$status} successfully.");
    }
}
