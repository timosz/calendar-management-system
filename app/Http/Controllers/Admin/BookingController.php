<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Zap\Facades\Zap;

class BookingController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $bookings = $user->schedules()
            ->appointments()
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
                    'status' => $schedule->metadata['status'] ?? 'pending',
                    'client_name' => $schedule->metadata['client_name'] ?? null,
                    'client_email' => $schedule->metadata['client_email'] ?? null,
                    'client_phone' => $schedule->metadata['client_phone'] ?? null,
                    'created_at' => $schedule->created_at->format('M j, Y g:i A'),
                ];
            });

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Bookings/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'periods' => 'required|array|min:1',
            'periods.*.start_time' => 'required|date_format:H:i',
            'periods.*.end_time' => 'required|date_format:H:i|after:periods.*.start_time',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email',
            'client_phone' => 'nullable|string|max:20',
        ]);

        $user = auth()->user();

        try {
            $schedule = Zap::for($user)
                ->named($request->name)
                ->description($request->description)
                ->appointment()
                ->from($request->start_date)
                ->withMetadata([
                    'status' => 'confirmed',
                    'client_name' => $request->client_name,
                    'client_email' => $request->client_email,
                    'client_phone' => $request->client_phone,
                ]);

            // Add periods
            foreach ($request->periods as $period) {
                $schedule->addPeriod($period['start_time'], $period['end_time']);
            }

            $schedule->save();

            return redirect()->route('admin.bookings.index')
                ->with('success', 'Booking created successfully.');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to create booking: '.$e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $user = auth()->user();

        $booking = $user->schedules()
            ->appointments()
            ->with('periods')
            ->findOrFail($id);

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'name' => $booking->name,
                'description' => $booking->description,
                'start_date' => $booking->start_date->format('Y-m-d'),
                'end_date' => $booking->end_date?->format('Y-m-d'),
                'periods' => $booking->periods->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'start_time' => $period->start_time,
                        'end_time' => $period->end_time,
                    ];
                }),
                'status' => $booking->metadata['status'] ?? 'pending',
                'client_name' => $booking->metadata['client_name'] ?? null,
                'client_email' => $booking->metadata['client_email'] ?? null,
                'client_phone' => $booking->metadata['client_phone'] ?? null,
                'created_at' => $booking->created_at->format('M j, Y g:i A'),
                'updated_at' => $booking->updated_at->format('M j, Y g:i A'),
            ],
        ]);
    }

    public function edit($id)
    {
        $user = auth()->user();

        $booking = $user->schedules()
            ->appointments()
            ->with('periods')
            ->findOrFail($id);

        return Inertia::render('Admin/Bookings/Edit', [
            'booking' => [
                'id' => $booking->id,
                'name' => $booking->name,
                'description' => $booking->description,
                'start_date' => $booking->start_date->format('Y-m-d'),
                'end_date' => $booking->end_date?->format('Y-m-d'),
                'periods' => $booking->periods->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'start_time' => $period->start_time,
                        'end_time' => $period->end_time,
                    ];
                }),
                'status' => $booking->metadata['status'] ?? 'pending',
                'client_name' => $booking->metadata['client_name'] ?? null,
                'client_email' => $booking->metadata['client_email'] ?? null,
                'client_phone' => $booking->metadata['client_phone'] ?? null,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'periods' => 'required|array|min:1',
            'periods.*.start_time' => 'required|date_format:H:i',
            'periods.*.end_time' => 'required|date_format:H:i|after:periods.*.start_time',
            'status' => 'required|in:pending,confirmed,rejected,cancelled',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email',
            'client_phone' => 'nullable|string|max:20',
        ]);

        $user = auth()->user();

        $booking = $user->schedules()
            ->appointments()
            ->findOrFail($id);

        try {
            $booking->update([
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'metadata' => array_merge($booking->metadata ?? [], [
                    'status' => $request->status,
                    'client_name' => $request->client_name,
                    'client_email' => $request->client_email,
                    'client_phone' => $request->client_phone,
                ]),
            ]);

            // Update periods
            $booking->periods()->delete();

            foreach ($request->periods as $period) {
                $booking->periods()->create([
                    'start_time' => $period['start_time'],
                    'end_time' => $period['end_time'],
                ]);
            }

            return redirect()->route('admin.bookings.index')
                ->with('success', 'Booking updated successfully.');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to update booking: '.$e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();

        $booking = $user->schedules()
            ->appointments()
            ->findOrFail($id);

        $booking->delete();

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }

    public function confirm($id)
    {
        $user = auth()->user();

        $booking = $user->schedules()
            ->appointments()
            ->findOrFail($id);

        $metadata = $booking->metadata ?? [];
        $metadata['status'] = 'confirmed';

        $booking->update(['metadata' => $metadata]);

        return back()->with('success', 'Booking confirmed successfully.');
    }

    public function reject($id)
    {
        $user = auth()->user();

        $booking = $user->schedules()
            ->appointments()
            ->findOrFail($id);

        $metadata = $booking->metadata ?? [];
        $metadata['status'] = 'rejected';

        $booking->update(['metadata' => $metadata]);

        return back()->with('success', 'Booking rejected successfully.');
    }
}
