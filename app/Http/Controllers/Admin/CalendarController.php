<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\Restriction;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Calendar/Index');
    }

    public function events(Request $request)
    {
        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();

        $events = [];

        // Get availabilities and convert to recurring events
        $availabilities = Availability::where('user_id', auth()->id())
            ->where('is_active', true)
            ->get();

        foreach ($availabilities as $availability) {
            $events[] = [
                'id' => 'availability-' . $availability->id,
                'title' => 'Available',
                'daysOfWeek' => [$availability->day_of_week],
                'startTime' => substr($availability->start_time, 0, 5), // HH:MM format
                'endTime' => substr($availability->end_time, 0, 5), // HH:MM format
                'backgroundColor' => '#86efac',
                'borderColor' => '#86efac',
                'display' => 'background',
                'extendedProps' => [
                    'type' => 'availability',
                    'data' => $availability
                ]
            ];
        }

        // Get restrictions
        $restrictions = Restriction::where('user_id', auth()->id())
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->orWhereBetween('end_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start->format('Y-m-d'))
                          ->where('end_date', '>=', $end->format('Y-m-d'));
                    });
            })
            ->get();

        foreach ($restrictions as $restriction) {
            $isAllDay = is_null($restriction->start_time) && is_null($restriction->end_time);

            if ($isAllDay) {
                // All-day event
                $events[] = [
                    'id' => 'restriction-' . $restriction->id,
                    'title' => $restriction->reason ?? ucfirst($restriction->type),
                    'start' => $restriction->start_date,
                    'end' => Carbon::parse($restriction->end_date)->addDay()->format('Y-m-d'),
                    'allDay' => true,
                    'backgroundColor' => '#d1d5db',
                    'borderColor' => '#9ca3af',
                    'textColor' => '#374151',
                    'extendedProps' => [
                        'type' => 'restriction',
                        'data' => $restriction
                    ]
                ];
            } else {
                // Timed event - create events for each day in the range
                $currentDate = Carbon::parse($restriction->start_date);
                $endDate = Carbon::parse($restriction->end_date);

                while ($currentDate->lte($endDate)) {
                    $events[] = [
                        'id' => 'restriction-' . $restriction->id . '-' . $currentDate->format('Y-m-d'),
                        'title' => $restriction->reason ?? ucfirst($restriction->type),
                        'start' => $currentDate->format('Y-m-d') . 'T' . substr($restriction->start_time, 0, 8),
                        'end' => $currentDate->format('Y-m-d') . 'T' . substr($restriction->end_time, 0, 8),
                        'allDay' => false,
                        'backgroundColor' => '#d1d5db',
                        'borderColor' => '#9ca3af',
                        'textColor' => '#374151',
                        'extendedProps' => [
                            'type' => 'restriction',
                            'data' => $restriction
                        ]
                    ];

                    $currentDate->addDay();
                }
            }
        }

        // Get bookings
        $bookings = Booking::where('user_id', auth()->id())
            ->where('booking_date', '>=', $start->format('Y-m-d'))
            ->where('booking_date', '<=', $end->format('Y-m-d'))
            ->get();

        foreach ($bookings as $booking) {
            $colors = [
                'confirmed' => ['bg' => '#3b82f6', 'border' => '#2563eb', 'text' => '#ffffff'],
                'pending' => ['bg' => '#f59e0b', 'border' => '#d97706', 'text' => '#ffffff'],
                'rejected' => ['bg' => '#ef4444', 'border' => '#dc2626', 'text' => '#ffffff'],
                'cancelled' => ['bg' => '#6b7280', 'border' => '#4b5563', 'text' => '#ffffff'],
            ];

            $color = $colors[$booking->status] ?? $colors['confirmed'];

            // booking_date is cast to Carbon\Carbon date object
            // start_time and end_time are strings in H:i:s format from database
            $events[] = [
                'id' => 'booking-' . $booking->id,
                'title' => $booking->client_name,
                'start' => $booking->booking_date->format('Y-m-d') . 'T' . $booking->start_time,
                'end' => $booking->booking_date->format('Y-m-d') . 'T' . $booking->end_time,
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'textColor' => $color['text'],
                'extendedProps' => [
                    'type' => 'booking',
                    'status' => $booking->status,
                    'data' => $booking
                ]
            ];
        }

        return response()->json($events);
    }
}
