<?php

namespace App\Actions\Restriction;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetRestrictionsAction
{
    /**
     * Get paginated restrictions for a user with optional filters.
     */
    public function execute(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $user->restrictions();

        // Filter by type if specified
        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        // Filter by date range if specified
        if (!empty($filters['from_date'])) {
            $fromDate = Carbon::parse($filters['from_date']);
            $query->where('end_date', '>=', $fromDate);
        }

        if (!empty($filters['to_date'])) {
            $toDate = Carbon::parse($filters['to_date']);
            $query->where('start_date', '<=', $toDate);
        }

        return $query
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->paginate($perPage);
    }
}
