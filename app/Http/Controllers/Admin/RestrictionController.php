<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Restriction\GetRestrictionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRestrictionRequest;
use App\Http\Requests\Admin\UpdateRestrictionRequest;
use App\Http\Resources\RestrictionFormResource;
use App\Http\Resources\RestrictionResource;
use App\Models\Restriction;
use App\Services\TimeSlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class RestrictionController extends Controller
{
    public function __construct(
        protected TimeSlotService $timeSlotService,
        protected GetRestrictionsAction $getRestrictionsAction
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'type' => $request->type,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ];

        $restrictions = $this->getRestrictionsAction
            ->execute(Auth::user(), array_filter($filters));

        return Inertia::render('Admin/Restrictions/Index', [
            'restrictions' => RestrictionResource::collection($restrictions),
            'types' => Restriction::getTypes(),
            'filters' => $filters,
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
            'restriction' => new RestrictionFormResource($restriction),
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
