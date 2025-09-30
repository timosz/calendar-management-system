<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get day names mapping
     */
    public static function getDayNames(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }

    /**
     * Get the day name for this availability
     */
    public function getDayNameAttribute(): string
    {
        return static::getDayNames()[$this->day_of_week];
    }

    /**
     * Scope to get active availabilities
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get availability for specific day(s)
     */
    public function scopeForDay($query, int|array $dayOfWeek)
    {
        if (is_array($dayOfWeek)) {
            return $query->whereIn('day_of_week', $dayOfWeek);
        }

        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Check if the given time range falls within this availability
     */
    public function coversTimeRange(string $startTime, string $endTime): bool
    {
        return $this->start_time <= $startTime && $this->end_time >= $endTime;
    }

    /**
     * Get duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        // ensure we always return a non-negative value
        return abs($end->diffInMinutes($start));
    }

    /**
     * Get duration in hours
     */
    public function getDurationInHours(): float
    {
        return $this->getDurationInMinutes() / 60;
    }
}
