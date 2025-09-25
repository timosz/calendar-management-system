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

        return Inertia::render('Admin/Availabilities/Index', [
            'availabilities' => $availabilities,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Availabilities/Create');
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
            'recurrence_type' => 'nullable|in:daily,weekly,monthly',
            'recurrence_days' => 'nullable|array',
        ]);

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

            // Add periods
            foreach ($request->periods as $period) {
                $schedule->addPeriod($period['start_time'], $period['end_time']);
            }

            // Handle recurrence
            if ($request->recurrence_type && $request->recurrence_days) {
                switch ($request->recurrence_type) {
                    case 'daily':
                        $schedule->daily();
                        break;
                    case 'weekly':
                        $schedule->weekly($request->recurrence_days);
                        break;
                    case 'monthly':
                        $schedule->monthly($request->recurrence_days);
                        break;
                }
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

        return Inertia::render('Admin/Availabilities/Edit', [
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
        ]);

        $user = auth()->user();

        $availability = $user->schedules()
            ->availability()
            ->findOrFail($id);

        try {
            $availability->update([
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            // Update periods - delete existing and create new ones
            $availability->periods()->delete();

            foreach ($request->periods as $period) {
                $availability->periods()->create([
                    'start_time' => $period['start_time'],
                    'end_time' => $period['end_time'],
                ]);
            }

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
}

