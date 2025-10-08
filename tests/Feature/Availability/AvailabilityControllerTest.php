<?php

use App\Models\Availability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('AvailabilityController', function () {
    it('shows weekly schedule correctly', function () {
        $response = $this->get(route('admin.availabilities.index'));

        $response->assertStatus(200)
            ->assertInertia(
                fn ($page) =>
                $page->component('Admin/Availabilities/Index')
                    ->has('weeklySchedule', 7)
                    ->has('timeSlots', 96)
            );
    });

    it('updates availabilities successfully', function () {
        // create an existing availability to be updated
        Availability::create([
            'user_id' => $this->user->id,
            'day_of_week' => 1,
            'start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'is_active' => true,
        ]);

        // ensure the availability exists
        $this->assertDatabaseHas('availabilities', [
            'user_id' => $this->user->id,
            'day_of_week' => 1,
            'start_time' => '10:00:00',
            'end_time' => '15:00:00',
        ]);

        $response = $this->put(route('admin.availabilities.update'), [
            'availabilities' => [
                [
                    'day_of_week' => 1,
                    'is_active' => true,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.availabilities.index'));

        $this->assertDatabaseHas('availabilities', [
            'user_id' => $this->user->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    });

    it('requires start time for active days', function () {
        $response = $this->put(route('admin.availabilities.update'), [
            'availabilities' => [
                [
                    'day_of_week' => 1,
                    'is_active' => true
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['availabilities.0.start_time']);
    });

    it('validates end time after start time', function () {
        $response = $this->put(route('admin.availabilities.update'), [
            'availabilities' => [
                [
                    'day_of_week' => 1,
                    'is_active' => true,
                    'start_time' => '17:00',
                    'end_time' => '09:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['availabilities.0.end_time']);
    });

    it('requires times for active days', function () {
        $response = $this->put(route('admin.availabilities.update'), [
            'availabilities' => [
                [
                    'day_of_week' => 1,
                    'is_active' => true,
                    'start_time' => null,
                    'end_time' => null,
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'availabilities.0.start_time',
            'availabilities.0.end_time',
        ]);
    });
});
