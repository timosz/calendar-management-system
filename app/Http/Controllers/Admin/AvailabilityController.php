<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Zap\Facades\Zap;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $availabilities = $user->schedules()
            ->availability()
            ->with('periods')
            ->latest()
            ->paginate(10)
            ->through(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'description' => $schedule->description,
                    'start_date' => $schedule->start_date->format('Y-m-d'),
                    'end_date' => $schedule->end_date?->format('Y-m-d'),
                    'periods' => $schedule->periods->map(function ($period) {
                        return [
                            'id' => $period->id,
                            'start_time' => $period->start_time,
                            'end_time' => $period->end_time,
                        ];
                    }),
                    'recurrence_pattern' => $schedule->recurrence_pattern,
                    'created_at' => $schedule->created_at->format('M j, Y'),
                ];
            });

        return Inertia::render('admin/availabilities/Index', [
            'availabilities' => $availabilities,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/availabilities/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'periods' => 'required|array|min:1',
            'periods.*.start_time' => 'required|date_format:H:i',
            'periods.*.end_time' => 'required|date_format:H:i|after:periods.*.start_time',
            'periods.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'recurrence_type' => 'nullable|in:weekly',
            'recurrence_days' => 'required_if:recurrence_type,weekly|array',
            'recurrence_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        $this->validatePeriodOverlaps($request->periods);

        $user = auth()->user();

        try {
            $schedule = Zap::for($user)
                ->named($request->name)
                ->description($request->description)
                ->availability()
                ->from($request->start_date);

            if ($request->end_date) {
                $schedule->to($request->end_date);
            }

            // Add periods (Zap will handle multiple periods automatically)
            foreach ($request->periods as $period) {
                $schedule->addPeriod($period['start_time'], $period['end_time']);
            }

            // Handle weekly recurrence
            if ($request->recurrence_type === 'weekly' && $request->recurrence_days) {
                $schedule->weekly($request->recurrence_days);
            }

            $schedule->save();

            return redirect()->route('admin.availabilities.index')
                ->with('success', 'Availability created successfully.');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to create availability: '.$e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $user = auth()->user();

        $availability = $user->schedules()
            ->availability()
            ->with('periods')
            ->findOrFail($id);

        return Inertia::render('Admin/Availabilities/Show', [
            'availability' => [
                'id' => $availability->id,
                'name' => $availability->name,
                'description' => $availability->description,
                'start_date' => $availability->start_date->format('Y-m-d'),
                'end_date' => $availability->end_date?->format('Y-m-d'),
                'periods' => $availability->periods->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'start_time' => $period->start_time,
                        'end_time' => $period->end_time,
                    ];
                }),
                'recurrence_pattern' => $availability->recurrence_pattern,
                'created_at' => $availability->created_at->format('M j, Y g:i A'),
                'updated_at' => $availability->updated_at->format('M j, Y g:i A'),
            ],
        ]);
    }

    public function edit($id)
    {
        $user = auth()->user();

        $availability = $user->schedules()
            ->availability()
            ->with('periods')
            ->findOrFail($id);

        // Group periods by day if we have recurrence pattern
        $periodsByDay = [];
        if ($availability->recurrence_pattern && isset($availability->recurrence_pattern['days'])) {
            foreach ($availability->recurrence_pattern['days'] as $day) {
                $periodsByDay[$day] = $availability->periods->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'start_time' => $period->start_time,
                        'end_time' => $period->end_time,
                    ];
                })->toArray();
            }
        }

        return Inertia::render('admin/availabilities/Edit', [
            'availability' => [
                'id' => $availability->id,
                'name' => $availability->name,
                'description' => $availability->description,
                'start_date' => $availability->start_date->format('Y-m-d'),
                'end_date' => $availability->end_date?->format('Y-m-d'),
                'periods' => $availability->periods->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'start_time' => $period->start_time,
                        'end_time' => $period->end_time,
                    ];
                }),
                'recurrence_pattern' => $availability->recurrence_pattern,
                'periods_by_day' => $periodsByDay, // Add this for easier frontend processing
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'periods' => 'required|array|min:1',
            'periods.*.start_time' => 'required|date_format:H:i',
            'periods.*.end_time' => 'required|date_format:H:i|after:periods.*.start_time',
            'periods.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'recurrence_type' => 'nullable|in:weekly',
            'recurrence_days' => 'required_if:recurrence_type,weekly|array',
            'recurrence_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        $this->validatePeriodOverlaps($request->periods);

        $user = auth()->user();

        $availability = $user->schedules()
            ->availability()
            ->findOrFail($id);

        try {
            // Delete the existing schedule and create a new one
            // This is necessary because Zap's fluent API is designed for creation
            $availability->delete();

            $schedule = Zap::for($user)
                ->named($request->name)
                ->description($request->description)
                ->availability()
                ->from($request->start_date);

            if ($request->end_date) {
                $schedule->to($request->end_date);
            }

            // Add periods (Zap will handle multiple periods automatically)
            foreach ($request->periods as $period) {
                $schedule->addPeriod($period['start_time'], $period['end_time']);
            }

            // Handle weekly recurrence
            if ($request->recurrence_type === 'weekly' && $request->recurrence_days) {
                $schedule->weekly($request->recurrence_days);
            }

            $schedule->save();

            return redirect()->route('admin.availabilities.index')
                ->with('success', 'Availability updated successfully.');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to update availability: '.$e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();

        $availability = $user->schedules()
            ->availability()
            ->findOrFail($id);

        $availability->delete();

        return redirect()->route('admin.availabilities.index')
            ->with('success', 'Availability deleted successfully.');
    }

    /**
     * Validate that periods don't overlap within the same day
     */
    private function validatePeriodOverlaps($periods)
    {
        $periodsByDay = collect($periods)->groupBy('day');

        foreach ($periodsByDay as $day => $dayPeriods) {
            $sortedPeriods = $dayPeriods->sortBy('start_time');

            for ($i = 0; $i < count($sortedPeriods) - 1; $i++) {
                $currentPeriod = $sortedPeriods->values()[$i];
                $nextPeriod = $sortedPeriods->values()[$i + 1];

                if ($currentPeriod['end_time'] > $nextPeriod['start_time']) {
                    throw new \Exception("Time periods overlap on {$day}. Please ensure periods don't overlap.");
                }
            }
        }
    }
}
