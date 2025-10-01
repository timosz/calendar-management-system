<?php

use App\Models\User;
use App\Models\Availability;

test('returns a successful response', function () {
    // Create a user with at least one active availability
    $user = User::factory()->create();

    Availability::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1, // Monday
        'start_time' => '09:00',
        'end_time' => '17:00',
        'is_active' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200);
});
