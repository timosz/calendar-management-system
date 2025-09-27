<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\AvailabilityPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the availability periods for the user.
     */
    public function availabilityPeriods(): HasMany
    {
        return $this->hasMany(AvailabilityPeriod::class);
    }

    /**
     * Get the active availability periods for the user.
     */
    public function activeAvailabilityPeriods(): HasMany
    {
        return $this->hasMany(AvailabilityPeriod::class)->where('is_active', true);
    }

    /**
     * Get the bookings for the user.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the confirmed bookings for the user.
     */
    public function confirmedBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->where('status', Booking::STATUS_CONFIRMED);
    }

    /**
     * Get the pending bookings for the user.
     */
    public function pendingBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->where('status', Booking::STATUS_PENDING);
    }

    /**
     * Get the unavailable periods for the user.
     */
    public function unavailablePeriods(): HasMany
    {
        return $this->hasMany(UnavailablePeriod::class);
    }

    /**
     * Get the current and future unavailable periods for the user.
     */
    public function currentAndFutureUnavailablePeriods(): HasMany
    {
        return $this->hasMany(UnavailablePeriod::class)
                    ->where('end_date', '>=', now()->toDateString());
    }
}
