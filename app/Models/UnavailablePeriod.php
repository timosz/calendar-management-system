<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UnavailablePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];

    /**
     * Get the user that owns the unavailable period.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get periods for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get periods that overlap with a given date range.
     */
    public function scopeOverlappingDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate)
              ->where('end_date', '>=', $startDate);
        });
    }

    /**
     * Scope to get periods that are active on a specific date.
     */
    public function scopeActiveOnDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date);
    }

    /**
     * Scope to get future periods.
     */
    public function scopeFuture($query)
    {
        return $query->where('start_date', '>=', now()->toDateString());
    }

    /**
     * Scope to get current and future periods.
     */
    public function scopeCurrentAndFuture($query)
    {
        return $query->where('end_date', '>=', now()->toDateString());
    }

    /**
     * Check if this period is all-day unavailable.
     */
    public function isAllDay(): bool
    {
        return is_null($this->start_time) && is_null($this->end_time);
    }

    /**
     * Check if this period affects a specific date and time range.
     */
    public function affectsDateTime($date, string $startTime = null, string $endTime = null): bool
    {
        // Check if the date falls within this unavailable period
        if (!$this->isDateInRange($date)) {
            return false;
        }

        // If this is an all-day unavailability, it affects any time
        if ($this->isAllDay()) {
            return true;
        }

        // If no time range is provided, just check if any part of the day is affected
        if (is_null($startTime) || is_null($endTime)) {
            return true;
        }

        // Check time overlap
        return $this->overlapsTimeRange($startTime, $endTime);
    }

    /**
     * Check if a date falls within this period's date range.
     */
    public function isDateInRange($date): bool
    {
        $checkDate = Carbon::parse($date)->toDateString();
        return $checkDate >= $this->start_date->toDateString() &&
               $checkDate <= $this->end_date->toDateString();
    }

    /**
     * Check if this period overlaps with a time range.
     */
    public function overlapsTimeRange(string $startTime, string $endTime): bool
    {
        if ($this->isAllDay()) {
            return true;
        }

        $periodStart = $this->start_time->format('H:i:s');
        $periodEnd = $this->end_time->format('H:i:s');

        return $startTime < $periodEnd && $endTime > $periodStart;
    }

    /**
     * Get the duration in days.
     */
    public function getDurationInDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Get formatted date range.
     */
    public function getDateRangeAttribute(): string
    {
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('M j, Y');
        }

        return $this->start_date->format('M j, Y') . ' - ' . $this->end_date->format('M j, Y');
    }

    /**
     * Get formatted time range (null if all day).
     */
    public function getTimeRangeAttribute(): ?string
    {
        if ($this->isAllDay()) {
            return null;
        }

        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    /**
     * Get display text for the period.
     */
    public function getDisplayTextAttribute(): string
    {
        $text = $this->date_range;

        if (!$this->isAllDay()) {
            $text .= ' (' . $this->time_range . ')';
        }

        if ($this->reason) {
            $text .= ' - ' . $this->reason;
        }

        return $text;
    }

    /**
     * Check if this period is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        $today = now()->toDateString();
        return $this->start_date->toDateString() <= $today &&
               $this->end_date->toDateString() >= $today;
    }

    /**
     * Check if this period is in the future.
     */
    public function isFuture(): bool
    {
        return $this->start_date->isFuture();
    }

    /**
     * Check if this period is in the past.
     */
    public function isPast(): bool
    {
        return $this->end_date->isPast();
    }
}
