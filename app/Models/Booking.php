<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

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
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get available booking statuses
     */
    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * Get the status label
     */
    public function getStatusLabelAttribute(): string
    {
        return static::getStatuses()[$this->status];
    }

    /**
     * Check if the booking is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if the booking is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the booking is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if the booking is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the booking can be confirmed
     */
    public function canBeConfirmed(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the booking can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the booking can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Get duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        // always return a positive duration
        return abs($end->diffInMinutes($start));
    }

    /**
     * Check if this booking conflicts with a time range on its booking date
     */
    public function conflictsWithTimeRange(string $startTime, string $endTime): bool
    {
        $bookingStart = $this->start_time->format('H:i');
        $bookingEnd = $this->end_time->format('H:i');

        // Check for time overlap: conflict if NOT (end <= start OR start >= end)
        return !($endTime <= $bookingStart || $startTime >= $bookingEnd);
    }

    /**
     * Check if this booking conflicts with another booking
     */
    public function conflictsWithBooking(Booking $otherBooking): bool
    {
        // Must be on the same date
        if (!$this->booking_date->eq($otherBooking->booking_date)) {
            return false;
        }

        return $this->conflictsWithTimeRange(
            $otherBooking->start_time->format('H:i'),
            $otherBooking->end_time->format('H:i')
        );
    }

    /**
     * Check if this booking falls within availability for its day
     */
    public function fallsWithinAvailability(): bool
    {
        $dayOfWeek = $this->booking_date->dayOfWeek;
        $availability = $this->user->availabilityForDay($dayOfWeek);

        if (!$availability || !$availability->is_active) {
            return false;
        }

        return $availability->coversTimeRange(
            $this->start_time->format('H:i'),
            $this->end_time->format('H:i')
        );
    }

    /**
     * Check if this booking conflicts with any restrictions
     */
    public function conflictsWithRestrictions(): bool
    {
        $restrictions = $this->user
            ->restrictions()
            ->affectingDateRange($this->booking_date, $this->booking_date)
            ->get();

        foreach ($restrictions as $restriction) {
            if ($restriction->conflictsWithTimeRange(
                $this->booking_date,
                $this->start_time->format('H:i'),
                $this->end_time->format('H:i')
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate if this booking can be placed (business rules)
     */
    public function isValidBooking(): array
    {
        $errors = [];

        // Check if falls within availability
        if (!$this->fallsWithinAvailability()) {
            $errors[] = 'Booking time falls outside of available hours.';
        }

        // Check for restriction conflicts
        if ($this->conflictsWithRestrictions()) {
            $errors[] = 'Booking conflicts with restricted periods.';
        }

        // Check for conflicts with other confirmed bookings
        $conflictingBookings = $this->user
            ->bookings()
            ->where('booking_date', $this->booking_date)
            ->where('status', 'confirmed')
            ->where('id', '!=', $this->id ?? 0) // Exclude self if updating
            ->get();

        foreach ($conflictingBookings as $existingBooking) {
            if ($this->conflictsWithBooking($existingBooking)) {
                $errors[] = 'Booking conflicts with existing confirmed booking.';
                break;
            }
        }

        return $errors;
    }

    /**
     * Scope to get bookings for a specific date range
     */
    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('booking_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get bookings by status
     */
    public function scopeStatus($query, string|array $status)
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    /**
     * Scope to get upcoming bookings
     */
    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString());
    }

    /**
     * Scope to get past bookings
     */
    public function scopePast($query)
    {
        return $query->where('booking_date', '<', now()->toDateString());
    }

    /**
     * Scope to get today's bookings
     */
    public function scopeToday($query)
    {
        return $query->where('booking_date', now()->toDateString());
    }

    /**
     * Scope to get this week's bookings
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('booking_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ]);
    }

    /**
     * Scope to get confirmed bookings
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to get pending bookings
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
