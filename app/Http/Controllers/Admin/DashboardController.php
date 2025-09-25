<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Zap\Models\Schedule;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get dashboard statistics
        $stats = [
            'totalAvailabilities' => $user->schedules()->availability()->count(),
            'totalUnavailabilities' => $user->schedules()->blocked()->count(),
            'totalBookings' => $user->schedules()->appointments()->count(),
            'pendingBookings' => $user->schedules()
                ->appointments()
                ->where('metadata->status', 'pending')
                ->count(),
        ];

        // Get recent bookings (last 5)
        $recentBookings = $user->schedules()
            ->appointments()
            ->with('periods')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'description' => $schedule->description,
                    'date' => $schedule->start_date->format('Y-m-d'),
                    'periods' => $schedule->periods->map(function ($period) {
                        return [
                            'start_time' => $period->start_time,
                            'end_time' => $period->end_time,
                        ];
                    }),
                    'status' => $schedule->metadata['status'] ?? 'pending',
                    'created_at' => $schedule->created_at->diffForHumans(),
                ];
            });

        // Get upcoming availabilities (next 5)
        $upcomingAvailabilities = $user->schedules()
            ->availability()
            ->with('periods')
            ->where('start_date', '>=', now()->startOfDay())
            ->orderBy('start_date')
            ->take(5)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'date' => $schedule->start_date->format('Y-m-d'),
                    'periods' => $schedule->periods->map(function ($period) {
                        return [
                            'start_time' => $period->start_time,
                            'end_time' => $period->end_time,
                        ];
                    }),
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentBookings' => $recentBookings,
            'upcomingAvailabilities' => $upcomingAvailabilities,
        ]);
    }
}
