<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Auth::user()->bookings();

        // Filter by status if specified
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        // Filter by date range if specified
        if ($request->filled('from_date')) {
            $fromDate = Carbon::parse($request->from_date);
            if ($request->filled('to_date')) {
                $toDate = Carbon::parse($request->to_date);
                $query->dateRange($fromDate, $toDate);
            } else {
                $query->where('booking_date', '>=', $fromDate);
            }
        } elseif ($request->filled('to_date')) {
            $toDate = Carbon::parse($request->to_date);
            $query->where('booking_date', '<=', $toDate);
        }

        // Filter by time period
        if ($request->filled('period')) {
            switch ($request->period) {
                case 'today':
                    $query->today();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
                case 'this_week':
                    $query->thisWeek();
                    break;
            }
        }

        $bookings = $query
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->through(function ($booking) {
                return [
                    'id' => $booking->id,
                    'client_name' => $booking->client_name,
                    'client_email' => $booking->client_email,
                    'client_phone' => $booking->client_phone,
                    'booking_date' => $booking->booking_date->format('Y-m-d'),
                    'booking_date_formatted' => $booking->booking_date->format('M d, Y'),
                    'start_time' => $booking->start_time->format('H:i'),
                    'end_time' => $booking->end_time->format('H:i'),
                    'duration_minutes' => $booking->getDurationInMinutes(),
                    'status' => $booking->status,
                    'status_label' => $booking->status_label,
                    'notes' => $booking->notes,
                    'created_at' => $booking->created_at->format('Y-m-d H:i'),
                    'can_be_confirmed' => $booking->canBeConfirmed(),
                    'can_be_rejected' => $booking->canBeRejected(),
                    'can_be_cancelled' => $booking->canBeCancelled(),
                ];
            });

        // Get statistics for the current filter
        $stats = $this->getBookingStats($request);

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
            'stats' => $stats,
            'filters' => [
                'status' => $request->status,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'period' => $request->period,
            ],
            'statuses' => Booking::getStatuses(),
            'periods' => [
                'today' => 'Today',
                'upcoming' => 'Upcoming',
                'past' => 'Past',
                'this_week' => 'This Week',
            ],
        ]);
    }

    public function show(Booking $booking): Response
    {
        // Check for validation issues
        $validationErrors = $booking->isValidBooking();

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'client_name' => $booking->client_name,
                'client_email' => $booking->client_email,
                'client_phone' => $booking->client_phone,
                'booking_date' => $booking->booking_date->format('Y-m-d'),
                'booking_date_formatted' => $booking->booking_date->format('l, F j, Y'),
                'start_time' => $booking->start_time->format('H:i'),
                'end_time' => $booking->end_time->format('H:i'),
                'duration_minutes' => $booking->getDurationInMinutes(),
                'status' => $booking->status,
                'status_label' => $booking->status_label,
                'notes' => $booking->notes,
                'google_calendar_event_id' => $booking->google_calendar_event_id,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,
                'can_be_confirmed' => $booking->canBeConfirmed(),
                'can_be_rejected' => $booking->canBeRejected(),
                'can_be_cancelled' => $booking->canBeCancelled(),
                'validation_errors' => $validationErrors,
                'has_conflicts' => !empty($validationErrors),
            ],
        ]);
    }

    public function confirm(Booking $booking): RedirectResponse
    {
        if (!$booking->canBeConfirmed()) {
            return redirect()
                ->back()
                ->withErrors(['status' => 'Only pending bookings can be confirmed.']);
        }

        // Validate booking before confirming
        $validationErrors = $booking->isValidBooking();
        if (!empty($validationErrors)) {
            return redirect()
                ->back()
                ->withErrors(['booking' => 'Cannot confirm booking: ' . implode(' ', $validationErrors)]);
        }

        $booking->update(['status' => 'confirmed']);

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'Booking confirmed successfully.');
    }

    public function reject(Booking $booking): RedirectResponse
    {
        if (!$booking->canBeRejected()) {
            return redirect()
                ->back()
                ->withErrors(['status' => 'Only pending bookings can be rejected.']);
        }

        $booking->update(['status' => 'rejected']);

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'Booking rejected successfully.');
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        if (!$booking->canBeCancelled()) {
            return redirect()
                ->back()
                ->withErrors(['status' => 'Only pending or confirmed bookings can be cancelled.']);
        }

        $booking->update(['status' => 'cancelled']);

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'Booking cancelled successfully.');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }

    /**
     * Get booking statistics based on current filters
     */
    private function getBookingStats(Request $request): array
    {
        $query = Auth::user()->bookings();

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('from_date')) {
            $fromDate = Carbon::parse($request->from_date);
            if ($request->filled('to_date')) {
                $toDate = Carbon::parse($request->to_date);
                $query->dateRange($fromDate, $toDate);
            } else {
                $query->where('booking_date', '>=', $fromDate);
            }
        } elseif ($request->filled('to_date')) {
            $toDate = Carbon::parse($request->to_date);
            $query->where('booking_date', '<=', $toDate);
        }

        if ($request->filled('period')) {
            switch ($request->period) {
                case 'today':
                    $query->today();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->past();
                    break;
                case 'this_week':
                    $query->thisWeek();
                    break;
            }
        }

        $allBookings = $query->get();

        return [
            'total' => $allBookings->count(),
            'pending' => $allBookings->where('status', 'pending')->count(),
            'confirmed' => $allBookings->where('status', 'confirmed')->count(),
            'rejected' => $allBookings->where('status', 'rejected')->count(),
            'cancelled' => $allBookings->where('status', 'cancelled')->count(),
            'total_duration_minutes' => $allBookings->sum(function ($booking) {
                return $booking->getDurationInMinutes();
            }),
        ];
    }

    /**
     * Bulk action handler for multiple bookings
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:confirm,reject,cancel,delete',
            'booking_ids' => 'required|array|min:1',
            'booking_ids.*' => 'exists:bookings,id',
        ]);

        $bookings = Auth::user()->bookings()->whereIn('id', $request->booking_ids)->get();
        $successCount = 0;
        $errors = [];

        foreach ($bookings as $booking) {
            try {
                switch ($request->action) {
                    case 'confirm':
                        if ($booking->canBeConfirmed()) {
                            $validationErrors = $booking->isValidBooking();
                            if (empty($validationErrors)) {
                                $booking->update(['status' => 'confirmed']);
                                $successCount++;
                            } else {
                                $errors[] = "Booking #{$booking->id} has validation errors.";
                            }
                        } else {
                            $errors[] = "Booking #{$booking->id} cannot be confirmed.";
                        }
                        break;

                    case 'reject':
                        if ($booking->canBeRejected()) {
                            $booking->update(['status' => 'rejected']);
                            $successCount++;
                        } else {
                            $errors[] = "Booking #{$booking->id} cannot be rejected.";
                        }
                        break;

                    case 'cancel':
                        if ($booking->canBeCancelled()) {
                            $booking->update(['status' => 'cancelled']);
                            $successCount++;
                        } else {
                            $errors[] = "Booking #{$booking->id} cannot be cancelled.";
                        }
                        break;

                    case 'delete':
                        $booking->delete();
                        $successCount++;
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = "Error processing booking #{$booking->id}: " . $e->getMessage();
            }
        }

        $message = "Successfully processed {$successCount} booking(s).";
        
        if (!empty($errors)) {
            $message .= ' Some bookings could not be processed: ' . implode(' ', $errors);
            return redirect()
                ->route('admin.bookings.index')
                ->with('warning', $message);
        }

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', $message);
    }

    /**
     * Export bookings to CSV
     */
    public function export(Request $request)
    {
        $query = Auth::user()->bookings();

        // Apply filters
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->dateRange(
                Carbon::parse($request->from_date),
                Carbon::parse($request->to_date)
            );
        }

        $bookings = $query
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        $csvContent = "ID,Client Name,Client Email,Client Phone,Booking Date,Start Time,End Time,Duration (minutes),Status,Notes,Created At\n";

        foreach ($bookings as $booking) {
            $csvContent .= implode(',', [
                $booking->id,
                '"' . str_replace('"', '""', $booking->client_name) . '"',
                $booking->client_email,
                $booking->client_phone ?? '',
                $booking->booking_date->format('Y-m-d'),
                $booking->start_time->format('H:i'),
                $booking->end_time->format('H:i'),
                $booking->getDurationInMinutes(),
                $booking->status,
                '"' . str_replace('"', '""', $booking->notes ?? '') . '"',
                $booking->created_at->format('Y-m-d H:i:s'),
            ]) . "\n";
        }

        $filename = 'bookings_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}