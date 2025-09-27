<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Restriction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'reason',
        'type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get available restriction types
     */
    public static function getTypes(): array
    {
        return [
            'holiday' => 'Holiday',
            'break' => 'Break',
            'meeting' => 'Meeting',
            'personal' => 'Personal',
            'maintenance' => 'Maintenance',
            'other' => 'Other',
        ];
    }

    /**
     * Check if this is an all-day restriction
     */
    public function isAllDay(): bool
    {
        return is_null($this->start_time) && is_null($this->end_time);
    }

    /**
     * Check if this restriction affects a specific date
     */
    public function affectsDate(Carbon $date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }

    /**
     * Check if this restriction conflicts with a time range on a specific date
     */
    public function conflictsWithTimeRange(Carbon $date, string $startTime, string $endTime): bool
    {
        if (!$this->affectsDate($date)) {
            return false;
        }

        // If it's all day, it conflicts with any time
        if ($this->isAllDay()) {
            return true;
        }

        // Check for time overlap: conflict if NOT (end <= start OR start >= end)
        return !($endTime <= $this->start_time || $startTime >= $this->end_time);
    }

    /**
     * Scope for restrictions affecting a date range
     */
    public function scopeAffectingDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope for restrictions of specific type(s)
     */
    public function scopeOfType($query, string|array $type)
    {
        if (is_array($type)) {
            return $query->whereIn('type', $type);
        }

        return $query->where('type', $type);
    }
}
