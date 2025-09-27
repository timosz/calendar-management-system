<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Booking::query()
            ->forUser(auth()->id())
            ->with('user');

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->withStatus($request->status);
        }

        // Filter by date range if provided
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->betweenDates($request->date_from, $request->date_to);
        }

        // Search by client name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('client_email', 'like', "%{$search}%");
            });
        }

        $bookings = $query
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => $request->only(['status', 'date_from', 'date_to', 'search']),
            'statusOptions' => [
                ['value' => 'all', 'label' => 'All Statuses'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'confirmed', 'label' => 'Confirmed'],
                ['value' => 'rejected', 'label' => 'Rejected'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Bookings/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:255',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:' . implode(',', Booking::getStatuses()),
            'notes' => 'nullable|string',
        ]);

        // Check for conflicts with existing bookings
        $conflicting = Booking::forUser(auth()->id())
            ->forDate($validated['booking_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->filter(function ($booking) use ($validated) {
                return $booking->overlaps($validated['start_time'] . ':00', $validated['end_time'] . ':00');
            });

        if ($conflicting->isNotEmpty()) {
            return back()->withErrors([
                'start_time' => 'This time slot conflicts with an existing booking.',
            ]);
        }

        Booking::create([
            'user_id' => auth()->id(),
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'],
            'client_phone' => $validated['client_phone'],
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking): Response
    {
        return Inertia::render('Admin/Bookings/Show', [
            'booking' => $booking->load('user'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking): Response
    {
        return Inertia::render('Admin/Bookings/Edit', [
            'booking' => $booking,
            'statusOptions' => collect(Booking::getStatuses())->map(function ($status) {
                return [
                    'value' => $status,
                    'label' => ucfirst($status),
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:255',
            'booking_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:' . implode(',', Booking::getStatuses()),
            'notes' => 'nullable|string',
        ]);

        // Check for conflicts with existing bookings (excluding current booking)
        $conflicting = Booking::forUser(auth()->id())
            ->forDate($validated['booking_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('id', '!=', $booking->id)
            ->get()
            ->filter(function ($existingBooking) use ($validated) {
                return $existingBooking->overlaps($validated['start_time'] . ':00', $validated['end_time'] . ':00');
            });

        if ($conflicting->isNotEmpty()) {
            return back()->withErrors([
                'start_time' => 'This time slot conflicts with an existing booking.',
            ]);
        }

        $booking->update([
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'],
            'client_phone' => $validated['client_phone'],
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }

    /**
     * Confirm the specified booking.
     */
    public function confirm(Booking $booking): RedirectResponse
    {
        if (!$booking->canBeModified()) {
            return back()->withErrors(['status' => 'This booking cannot be modified.']);
        }

        $booking->confirm();

        return back()->with('success', 'Booking confirmed successfully.');
    }

    /**
     * Reject the specified booking.
     */
    public function reject(Booking $booking): RedirectResponse
    {
        if (!$booking->canBeModified()) {
            return back()->withErrors(['status' => 'This booking cannot be modified.']);
        }

        $booking->reject();

        return back()->with('success', 'Booking rejected successfully.');
    }

    /**
     * Cancel the specified booking.
     */
    public function cancel(Booking $booking): RedirectResponse
    {
        if (!$booking->canBeModified()) {
            return back()->withErrors(['status' => 'This booking cannot be modified.']);
        }

        $booking->cancel();

        return back()->with('success', 'Booking cancelled successfully.');
    }
}
