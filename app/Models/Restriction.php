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

        // Ranges overlap if start is before restriction ends AND end is after restriction starts
        return $startTime < $this->end_time && $endTime > $this->start_time;
    }

    /**
     * Scope for restrictions affecting a date range
     */
    public function scopeAffectingDateRange($query, $startDate, $endDate)
    {
        return $query->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
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
