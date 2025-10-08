<?php

namespace App\Http\Requests\Admin;

use App\Models\Restriction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UpdateRestrictionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i|required_with:end_time',
            'end_time' => 'nullable|date_format:H:i|after:start_time|required_with:start_time',
            'reason' => 'nullable|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(Restriction::getTypes())),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->checkForBookingConflicts($validator);
        });
    }

    /**
     * Check for conflicts with existing confirmed bookings.
     */
    protected function checkForBookingConflicts($validator): void
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        $conflictingBookings = Auth::user()
            ->bookings()
            ->where('status', 'confirmed')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->where('id', '!=', $this->route('restriction')->id ?? null)
            ->get();

        if ($conflictingBookings->isEmpty()) {
            return;
        }

        // If all day restriction, all bookings in date range conflict
        if (empty($this->start_time) && empty($this->end_time)) {
            $validator->errors()->add(
                'start_date',
                'This period conflicts with existing confirmed bookings.'
            );
            return;
        }

        // Check for time conflicts
        foreach ($conflictingBookings as $booking) {
            $bookingDate = $booking->booking_date;

            if ($bookingDate->between($startDate, $endDate)) {
                // Check time overlap
                $restrictionStart = $this->start_time;
                $restrictionEnd = $this->end_time;
                $bookingStart = $booking->start_time;
                $bookingEnd = $booking->end_time;

                if (!($bookingEnd <= $restrictionStart || $bookingStart >= $restrictionEnd)) {
                    $validator->errors()->add(
                        'start_time',
                        'This time period conflicts with an existing confirmed booking.'
                    );
                    return;
                }
            }
        }
    }
}
