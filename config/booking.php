<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Booking Time Settings
    |--------------------------------------------------------------------------
    |
    | Configure the time slot settings for the booking system.
    |
    */

    // Time slot interval in minutes (how often slots start)
    'slot_interval_minutes' => env('BOOKING_SLOT_INTERVAL', 30),

    // Duration of each booking slot in minutes
    'slot_duration_minutes' => env('BOOKING_SLOT_DURATION', 60),

    /*
    |--------------------------------------------------------------------------
    | Booking Window
    |--------------------------------------------------------------------------
    |
    | How many weeks in advance can clients book appointments.
    |
    */

    'max_weeks_ahead' => env('BOOKING_MAX_WEEKS_AHEAD', 8),

    /*
    |--------------------------------------------------------------------------
    | User Settings
    |--------------------------------------------------------------------------
    |
    | Configure which user's availability to use for the booking system.
    | If null, it will use the first user in the database.
    |
    */

    'default_user_id' => env('BOOKING_DEFAULT_USER_ID', null),
];
