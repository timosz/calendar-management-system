<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Zap\Facades\Zap;

class UnavailabilityController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $unavailabilities = $user->schedules()
            ->blocked()
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
                    'created_at' => $schedule->created_at->format('M j, Y'),
                ];
            });

        return Inertia::render('Admin/Unavailabilities/Index', [
            'unavailabilities' => $unavailabilities,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Unavailabilities/Create');
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
                ->blocked()
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

            return redirect()->route('admin.unavailabilities.index')
                ->with('success', 'Unavailability created successfully.');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to create unavailability: '.$e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $user = auth()->user();

        $unavailability = $user->schedules()
            ->blocked()
            ->with('periods')
            ->findOrFail($id);

        return Inertia::render('Admin/Unavailabilities/Show', [
            'unavailability' => [
                'id' => $unavailability->id,
                'name' => $unavailability->name,
                'description' => $unavailability->description,
                'start_date' => $unavailability->start_date->format('Y-m-d'),
                'end_date' => $unavailability->end_date?->format('Y-m-d'),
                'periods' => $unavailability->periods->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'start_time' => $period->start_time,
                        'end_time' => $period->end_time,
                    ];
                }),
                'created_at' => $unavailability->created_at->format('M j, Y g:i A'),
                'updated_at' => $unavailability->updated_at->format('M j, Y g:i A'),
            ],
        ]);
    }

    public function edit($id)
    {
        $user = auth()->user();

        $unavailability = $user->schedules()
            ->blocked()
            ->with('periods')
            ->findOrFail($id);

        return Inertia::render('Admin/Unavailabilities/Edit', [
            'unavailability' => [
                'id' => $unavailability->id,
                'name' => $unavailability->name,
                'description' => $unavailability->description,
                'start_date' => $unavailability->start_date->format('Y-m-d'),
                'end_date' => $unavailability->end_date?->format('Y-m-d'),
                'periods' => $unavailability->periods->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'start_time' => $period->start_time,
                        'end_time' => $period->end_time,
                    ];
                }),
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

        $unavailability = $user->schedules()
            ->blocked()
            ->findOrFail($id);

        try {
            $unavailability->update([
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            // Update periods
            $unavailability->periods()->delete();

            foreach ($request->periods as $period) {
                $unavailability->periods()->create([
                    'start_time' => $period['start_time'],
                    'end_time' => $period['end_time'],
                ]);
            }

            return redirect()->route('admin.unavailabilities.index')
                ->with('success', 'Unavailability updated successfully.');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to update unavailability: '.$e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();

        $unavailability = $user->schedules()
            ->blocked()
            ->findOrFail($id);

        $unavailability->delete();

        return redirect()->route('admin.unavailabilities.index')
            ->with('success', 'Unavailability deleted successfully.');
    }
}
