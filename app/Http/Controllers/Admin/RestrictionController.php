<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restriction;
use App\Services\TimeSlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class RestrictionController extends Controller
{
    public function __construct(
        protected TimeSlotService $timeSlotService
    ) {
    }

    public function index(Request $request): Response
    {
        $query = Auth::user()->restrictions();

        // Filter by type if specified
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by date range if specified
        if ($request->filled('from_date')) {
            $fromDate = Carbon::parse($request->from_date);
            $query->where('end_date', '>=', $fromDate);
        }

        if ($request->filled('to_date')) {
            $toDate = Carbon::parse($request->to_date);
            $query->where('start_date', '<=', $toDate);
        }

        $restrictions = $query
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->through(function ($restriction) {
                return [
                    'id' => $restriction->id,
                    'start_date' => $restriction->start_date->format('Y-m-d'),
                    'end_date' => $restriction->end_date->format('Y-m-d'),
                    'start_time' => $restriction->start_time?->format('H:i'),
                    'end_time' => $restriction->end_time?->format('H:i'),
                    'is_all_day' => $restriction->isAllDay(),
                    'reason' => $restriction->reason,
                    'type' => $restriction->type,
                    'type_label' => Restriction::getTypes()[$restriction->type],
                ];
            });

        return Inertia::render('Admin/Restrictions/Index', [
            'restrictions' => $restrictions,
            'types' => Restriction::getTypes(),
            'filters' => [
                'type' => $request->type,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Restrictions/Create', [
            'types' => Restriction::getTypes(),
            'timeSlots' => $this->timeSlotService->generateTimeSlots(30), // 30-minute intervals for restrictions
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i|required_with:end_time',
            'end_time' => 'nullable|date_format:H:i|after:start_time|required_with:start_time',
            'reason' => 'nullable|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(Restriction::getTypes())),
        ]);

        // Check for conflicts with existing bookings
        $this->checkForBookingConflicts($validated);

        Auth::user()->restrictions()->create($validated);

        return redirect()
            ->route('admin.restrictions.index')
            ->with('success', 'Restriction created successfully.');
    }

    public function show(Restriction $restriction): Response
    {
        return Inertia::render('Admin/Restrictions/Show', [
            'restriction' => [
                'id' => $restriction->id,
                'start_date' => $restriction->start_date->format('Y-m-d'),
                'end_date' => $restriction->end_date->format('Y-m-d'),
                'start_time' => $restriction->start_time?->format('H:i'),
                'end_time' => $restriction->end_time?->format('H:i'),
                'is_all_day' => $restriction->isAllDay(),
                'reason' => $restriction->reason,
                'type' => $restriction->type,
                'type_label' => Restriction::getTypes()[$restriction->type],
                'created_at' => $restriction->created_at,
                'updated_at' => $restriction->updated_at,
            ],
        ]);
    }

    public function edit(Restriction $restriction): Response
    {
        return Inertia::render('Admin/Restrictions/Edit', [
            'restriction' => [
                'id' => $restriction->id,
                'start_date' => $restriction->start_date->format('Y-m-d'),
                'end_date' => $restriction->end_date->format('Y-m-d'),
                'start_time' => $restriction->start_time?->format('H:i'),
                'end_time' => $restriction->end_time?->format('H:i'),
                'reason' => $restriction->reason,
                'type' => $restriction->type,
            ],
            'types' => Restriction::getTypes(),
            'timeSlots' => $this->timeSlotService->generateTimeSlots(30),
        ]);
    }

    public function update(Request $request, Restriction $restriction): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i|required_with:end_time',
            'end_time' => 'nullable|date_format:H:i|after:start_time|required_with:start_time',
            'reason' => 'nullable|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(Restriction::getTypes())),
        ]);

        // Check for conflicts with existing bookings
        $this->checkForBookingConflicts($validated, $restriction);

        $restriction->update($validated);

        return redirect()
            ->route('admin.restrictions.index')
            ->with('success', 'Restriction updated successfully.');
    }

    public function destroy(Restriction $restriction): RedirectResponse
    {
        $restriction->delete();

        return redirect()
            ->route('admin.restrictions.index')
            ->with('success', 'Restriction deleted successfully.');
    }

    /**
     * Check for conflicts with existing confirmed bookings
     */
    private function checkForBookingConflicts(array $data, ?Restriction $excludeRestriction = null): void
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $conflictingBookings = Auth::user()
            ->bookings()
            ->where('status', 'confirmed')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->get();

        if ($conflictingBookings->isEmpty()) {
            return;
        }

        // If all day restriction, all bookings in date range conflict
        if (empty($data['start_time']) && empty($data['end_time'])) {
            $validator = validator([], []);
            $validator->errors()->add(
                'start_date',
                'This period conflicts with existing confirmed bookings.'
            );
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Check for time conflicts
        foreach ($conflictingBookings as $booking) {
            $bookingDate = $booking->booking_date;

            if ($bookingDate->between($startDate, $endDate)) {
                // Check time overlap
                $restrictionStart = $data['start_time'];
                $restrictionEnd = $data['end_time'];
                $bookingStart = $booking->start_time->format('H:i');
                $bookingEnd = $booking->end_time->format('H:i');

                if (!($bookingEnd <= $restrictionStart || $bookingStart >= $restrictionEnd)) {
                    $validator = validator([], []);
                    $validator->errors()->add(
                        'start_time',
                        'This time period conflicts with an existing confirmed booking.'
                    );
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
            }
        }
    }
}
