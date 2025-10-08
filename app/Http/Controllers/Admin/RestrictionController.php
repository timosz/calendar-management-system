<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRestrictionRequest;
use App\Http\Requests\Admin\UpdateRestrictionRequest;
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
                    'start_time' => $restriction->start_time,
                    'end_time' => $restriction->end_time,
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
            'timeSlots' => $this->timeSlotService->generateTimeOptions(30),
        ]);
    }

    public function store(StoreRestrictionRequest $request): RedirectResponse
    {
        Auth::user()->restrictions()->create($request->validated());

        return redirect()
            ->route('admin.restrictions.index')
            ->with('success', 'Restriction created successfully.');
    }

    public function edit(Restriction $restriction): Response
    {
        return Inertia::render('Admin/Restrictions/Edit', [
            'restriction' => [
                'id' => $restriction->id,
                'start_date' => $restriction->start_date->format('Y-m-d'),
                'end_date' => $restriction->end_date->format('Y-m-d'),
                'start_time' => $restriction->start_time ? substr($restriction->start_time, 0, 5) : null,
                'end_time' => $restriction->end_time ? substr($restriction->end_time, 0, 5) : null,
                'reason' => $restriction->reason,
                'type' => $restriction->type,
            ],
            'types' => Restriction::getTypes(),
            'timeSlots' => $this->timeSlotService->generateTimeOptions(30),
        ]);
    }

    public function update(UpdateRestrictionRequest $request, Restriction $restriction): RedirectResponse
    {
        $restriction->update($request->validated());

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
}
