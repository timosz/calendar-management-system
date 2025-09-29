<?php

use App\Models\Booking;
use App\Models\Restriction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('RestrictionController', function () {
    it('shows restrictions index', function () {
        Restriction::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->get(route('admin.restrictions.index'));

        $response->assertStatus(200)
            ->assertInertia(
                fn ($page) =>
                $page->component('Admin/Restrictions/Index')
                    ->has('restrictions.data', 3)
                    ->has('types')
            );
    });

    it('filters restrictions by type', function () {
        Restriction::factory()->create(['user_id' => $this->user->id, 'type' => 'holiday']);
        Restriction::factory()->create(['user_id' => $this->user->id, 'type' => 'break']);

        $response = $this->get(route('admin.restrictions.index', ['type' => 'holiday']));

        $response->assertStatus(200)
            ->assertInertia(
                fn ($page) =>
                $page->has('restrictions.data', 1)
            );
    });

    it('shows create form', function () {
        $response = $this->get(route('admin.restrictions.create'));

        $response->assertStatus(200)
            ->assertInertia(
                fn ($page) =>
                $page->component('Admin/Restrictions/Create')
                    ->has('types')
                    ->has('timeSlots')
            );
    });

    it('stores all-day restriction', function () {
        $response = $this->post(route('admin.restrictions.store'), [
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-05',
            'start_time' => null,
            'end_time' => null,
            'type' => 'holiday',
            'reason' => 'Vacation',
        ]);

        $response->assertRedirect(route('admin.restrictions.index'));

        $this->assertDatabaseHas('restrictions', [
            'user_id' => $this->user->id,
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-05',
            'type' => 'holiday',
            'reason' => 'Vacation',
        ]);
    });

    it('stores partial-day restriction', function () {
        $response = $this->post(route('admin.restrictions.store'), [
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-01',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'type' => 'break',
            'reason' => 'Lunch break',
        ]);

        $response->assertRedirect(route('admin.restrictions.index'));

        $this->assertDatabaseHas('restrictions', [
            'user_id' => $this->user->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);
    });

    it('validates end time after start time', function () {
        $response = $this->post(route('admin.restrictions.store'), [
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-01',
            'start_time' => '17:00',
            'end_time' => '09:00',
            'type' => 'other',
        ]);

        $response->assertSessionHasErrors(['end_time']);
    });

    it('requires times together', function () {
        $response = $this->post(route('admin.restrictions.store'), [
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-01',
            'start_time' => '12:00',
            'end_time' => null,
            'type' => 'break',
        ]);

        $response->assertSessionHasErrors(['end_time']);
    });

    it('prevents conflict with confirmed booking', function () {
        // Create a confirmed booking
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'booking_date' => '2025-10-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'confirmed',
        ]);

        // Try to create overlapping restriction
        $response = $this->post(route('admin.restrictions.store'), [
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'type' => 'meeting',
        ]);

        $response->assertSessionHasErrors(['start_time']);
    });

    it('shows edit form', function () {
        $restriction = Restriction::factory()->create(['user_id' => $this->user->id]);

        $response = $this->get(route('admin.restrictions.edit', $restriction));

        $response->assertStatus(200)
            ->assertInertia(
                fn ($page) =>
                $page->component('Admin/Restrictions/Edit')
                    ->has('restriction')
                    ->has('types')
                    ->has('timeSlots')
            );
    });

    it('updates restriction', function () {
        $restriction = Restriction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'break',
        ]);

        $response = $this->put(route('admin.restrictions.update', $restriction), [
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-01',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'type' => 'meeting',
            'reason' => 'Updated reason',
        ]);

        $response->assertRedirect(route('admin.restrictions.index'));

        $this->assertDatabaseHas('restrictions', [
            'id' => $restriction->id,
            'type' => 'meeting',
            'reason' => 'Updated reason',
        ]);
    });

    it('deletes restriction', function () {
        $restriction = Restriction::factory()->create(['user_id' => $this->user->id]);

        $response = $this->delete(route('admin.restrictions.destroy', $restriction));

        $response->assertRedirect(route('admin.restrictions.index'));

        $this->assertDatabaseMissing('restrictions', [
            'id' => $restriction->id,
        ]);
    });
});
