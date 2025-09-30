<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {
    }

    /**
     * Display the booking interface
     */
    public function index(Request $request)
    {
        $maxWeeks = $this->availabilityService->getMaxWeeksAhead();
        $currentWeek = $request->integer('week', 1);
        $debugMode = $request->boolean('debug', false);

        // Validate week range
        if ($currentWeek < 1 || $currentWeek > $maxWeeks) {
            $currentWeek = 1;
        }

        // Get the configured user or first user
        $userId = config('booking.default_user_id');
        $user = $userId ? User::find($userId) : User::first();

        if (!$user) {
            abort(503, 'Service not available. Please contact administrator.');
        }

        // Calculate start date for the selected week
        $startDate = Carbon::now()->startOfWeek()->addWeeks($currentWeek - 1);

        // Get available slots for the week
        $availableSlots = $this->availabilityService->getAvailableSlotsForWeek(
            $user,
            $startDate,
            $debugMode
        );

        return Inertia::render('Booking', [
            'availableSlots' => $availableSlots,
            'currentWeek' => $currentWeek,
            'maxWeeks' => $maxWeeks,
            'debugMode' => $debugMode,
        ]);
    }

    /**
     * Store a new booking
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:255',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
        ]);

        // Get the configured user or first user
        $userId = config('booking.default_user_id');
        $user = $userId ? User::find($userId) : User::first();

        if (!$user) {
            return back()->withErrors(['error' => 'Service not available.']);
        }

        // Create a temporary booking to validate
        $booking = new Booking([
            'user_id' => $user->id,
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'],
            'client_phone' => $validated['client_phone'],
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        // Validate the booking
        $errors = $booking->isValidBooking();

        if (!empty($errors)) {
            return back()->withErrors(['error' => implode(' ', $errors)]);
        }

        // Save the booking
        $booking->save();

        // TODO: Send notification email to admin
        // TODO: Send confirmation email to client

        return redirect()
            ->route('booking')
            ->with('success', 'Your booking request has been submitted. You will receive a confirmation email once it is approved.');
    }
}
