<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnavailablePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class UnavailablePeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = UnavailablePeriod::query()
            ->forUser(auth()->id())
            ->with('user');

        // Filter by status (current, future, past)
        if ($request->has('filter')) {
            match ($request->filter) {
                'current' => $query->where('start_date', '<=', now()->toDateString())
                    ->where('end_date', '>=', now()->toDateString()),
                'future' => $query->future(),
                'past' => $query->where('end_date', '<', now()->toDateString()),
                default => $query->currentAndFuture(),
            };
        } else {
            $query->currentAndFuture();
        }

        // Search by reason
        if ($request->has('search') && $request->search) {
            $query->where('reason', 'like', '%' . $request->search . '%');
        }

        $unavailablePeriods = $query
            ->orderBy('start_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/UnavailablePeriods/Index', [
            'unavailablePeriods' => $unavailablePeriods,
            'filters' => $request->only(['filter', 'search']),
            'filterOptions' => [
                ['value' => 'all', 'label' => 'Current & Future'],
                ['value' => 'current', 'label' => 'Currently Active'],
                ['value' => 'future', 'label' => 'Future'],
                ['value' => 'past', 'label' => 'Past'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/UnavailablePeriods/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
        ]);

        // Validate that both start_time and end_time are provided together or both are null
        if (($validated['start_time'] && !$validated['end_time']) ||
            (!$validated['start_time'] && $validated['end_time'])) {
            return back()->withErrors([
                'start_time' => 'Both start time and end time must be provided for partial day unavailability, or leave both empty for all-day.',
            ]);
        }

        // Check for overlapping periods
        $overlapping = UnavailablePeriod::forUser(auth()->id())
            ->overlappingDates($validated['start_date'], $validated['end_date'])
            ->get()
            ->filter(function ($period) use ($validated) {
                // If current period is all-day, it overlaps with any existing period
                if (!$validated['start_time'] && !$validated['end_time']) {
                    return true;
                }

                // If existing period is all-day, it overlaps
                if ($period->isAllDay()) {
                    return true;
                }

                // If both have times, check time overlap
                if ($validated['start_time'] && $validated['end_time']) {
                    return $period->overlapsTimeRange($validated['start_time'] . ':00', $validated['end_time'] . ':00');
                }

                return false;
            });

        if ($overlapping->isNotEmpty()) {
            return back()->withErrors([
                'start_date' => 'This period overlaps with an existing unavailable period.',
            ]);
        }

        UnavailablePeriod::create([
            'user_id' => auth()->id(),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.unavailable-periods.index')
            ->with('success', 'Unavailable period created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(UnavailablePeriod $unavailablePeriod): Response
    {
        return Inertia::render('Admin/UnavailablePeriods/Show', [
            'unavailablePeriod' => $unavailablePeriod->load('user'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UnavailablePeriod $unavailablePeriod): Response
    {
        return Inertia::render('Admin/UnavailablePeriods/Edit', [
            'unavailablePeriod' => $unavailablePeriod,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UnavailablePeriod $unavailablePeriod): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
        ]);

        // Validate that both start_time and end_time are provided together or both are null
        if (($validated['start_time'] && !$validated['end_time']) ||
            (!$validated['start_time'] && $validated['end_time'])) {
            return back()->withErrors([
                'start_time' => 'Both start time and end time must be provided for partial day unavailability, or leave both empty for all-day.',
            ]);
        }

        // Check for overlapping periods (excluding current period)
        $overlapping = UnavailablePeriod::forUser(auth()->id())
            ->overlappingDates($validated['start_date'], $validated['end_date'])
            ->where('id', '!=', $unavailablePeriod->id)
            ->get()
            ->filter(function ($period) use ($validated) {
                // If current period is all-day, it overlaps with any existing period
                if (!$validated['start_time'] && !$validated['end_time']) {
                    return true;
                }

                // If existing period is all-day, it overlaps
                if ($period->isAllDay()) {
                    return true;
                }

                // If both have times, check time overlap
                if ($validated['start_time'] && $validated['end_time']) {
                    return $period->overlapsTimeRange($validated['start_time'] . ':00', $validated['end_time'] . ':00');
                }

                return false;
            });

        if ($overlapping->isNotEmpty()) {
            return back()->withErrors([
                'start_date' => 'This period overlaps with an existing unavailable period.',
            ]);
        }

        $unavailablePeriod->update([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.unavailable-periods.index')
            ->with('success', 'Unavailable period updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UnavailablePeriod $unavailablePeriod): RedirectResponse
    {
        $unavailablePeriod->delete();

        return redirect()->route('admin.unavailable-periods.index')
            ->with('success', 'Unavailable period deleted successfully.');
    }
}
