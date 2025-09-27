<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityPeriod;
use App\Models\Booking;
use App\Models\UnavailablePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request): Response
    {
        $userId = auth()->id();
        $today = now();
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // Basic statistics
        $stats = [
            'total_bookings' => Booking::forUser($userId)->count(),
            'pending_bookings' => Booking::forUser($userId)->pending()->count(),
            'confirmed_bookings' => Booking::forUser($userId)->confirmed()->count(),
            'this_week_bookings' => Booking::forUser($userId)
                ->betweenDates($startOfWeek->toDateString(), $endOfWeek->toDateString())
                ->count(),
            'this_month_bookings' => Booking::forUser($userId)
                ->betweenDates($startOfMonth->toDateString(), $endOfMonth->toDateString())
                ->count(),
            'active_availability_periods' => AvailabilityPeriod::forUser($userId)->active()->count(),
            'current_unavailable_periods' => UnavailablePeriod::forUser($userId)
                ->activeOnDate($today->toDateString())
                ->count(),
        ];

        // Recent bookings
        $recentBookings = Booking::forUser($userId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Upcoming bookings
        $upcomingBookings = Booking::forUser($userId)
            ->where('booking_date', '>=', $today->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('booking_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(10)
            ->get();

        // Today's bookings
        $todaysBookings = Booking::forUser($userId)
            ->forDate($today->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('start_time', 'asc')
            ->get();

        // Current unavailable periods
        $currentUnavailablePeriods = UnavailablePeriod::forUser($userId)
            ->activeOnDate($today->toDateString())
            ->get();

        // Booking status distribution for chart
        $statusDistribution = Booking::forUser($userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Weekly booking trend (last 8 weeks)
        $weeklyTrend = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = $today->copy()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            $count = Booking::forUser($userId)
                ->betweenDates($weekStart->toDateString(), $weekEnd->toDateString())
                ->count();

            $weeklyTrend[] = [
                'week' => $weekStart->format('M j'),
                'bookings' => $count,
            ];
        }

        // Monthly stats for the current year
        $monthlyStats = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = $today->copy()->month($month)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            // Only include months up to current month
            if ($monthStart->lte($today)) {
                $count = Booking::forUser($userId)
                    ->betweenDates($monthStart->toDateString(), $monthEnd->toDateString())
                    ->count();

                $monthlyStats[] = [
                    'month' => $monthStart->format('M'),
                    'bookings' => $count,
                ];
            }
        }

        // Availability summary
        $availabilitySummary = AvailabilityPeriod::forUser($userId)
            ->active()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week')
            ->map(function ($periods, $day) {
                return [
                    'day' => $this->getDayName($day),
                    'periods' => $periods->map(function ($period) {
                        return [
                            'time_range' => $period->time_range,
                            'is_active' => $period->is_active,
                        ];
                    }),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentBookings' => $recentBookings,
            'upcomingBookings' => $upcomingBookings,
            'todaysBookings' => $todaysBookings,
            'currentUnavailablePeriods' => $currentUnavailablePeriods,
            'statusDistribution' => $statusDistribution,
            'weeklyTrend' => $weeklyTrend,
            'monthlyStats' => $monthlyStats,
            'availabilitySummary' => $availabilitySummary,
            'currentDate' => $today->toDateString(),
            'currentTime' => $today->format('H:i'),
        ]);
    }

    /**
     * Get day name from day number.
     */
    private function getDayName(int $dayNumber): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$dayNumber] ?? 'Unknown';
    }
}
