<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'client_name',
        'client_email',
        'client_phone',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'google_calendar_event_id',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Scope to get bookings by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending bookings.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope to get bookings for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('booking_date', $date);
    }

    /**
     * Scope to get bookings for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get bookings within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('booking_date', [$startDate, $endDate]);
    }

    /**
     * Check if this booking overlaps with another time range on the same date.
     */
    public function overlaps(string $startTime, string $endTime, $date = null): bool
    {
        // If date is provided, check if it's the same date
        if ($date && $this->booking_date->format('Y-m-d') !== $date) {
            return false;
        }

        $bookingStart = $this->start_time->format('H:i:s');
        $bookingEnd = $this->end_time->format('H:i:s');

        return $startTime < $bookingEnd && $endTime > $bookingStart;
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    /**
     * Get the duration in minutes.
     */
    public function getDurationAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_CONFIRMED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Mark booking as confirmed.
     */
    public function confirm(): bool
    {
        return $this->update(['status' => self::STATUS_CONFIRMED]);
    }

    /**
     * Mark booking as rejected.
     */
    public function reject(): bool
    {
        return $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Mark booking as cancelled.
     */
    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Check if booking can be modified.
     */
    public function canBeModified(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    /**
     * Check if booking is active (confirmed or pending).
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }
}
