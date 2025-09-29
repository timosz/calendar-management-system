<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);
});

describe('Frontend Composables', function () {
    it('handles day toggle correctly', function () {
        $this->actingAs($this->user);
        
        $page = visit('/admin/availabilities');
        
        $page->click('#day-1')
            ->assertSee('Active')
            ->wait(0.5)
            ->click('#day-1')
            ->assertSee('Inactive');
    });

    it('shows duration calculation', function () {
        $this->actingAs($this->user);
        
        $page = visit('/admin/availabilities');
        
        $page->click('#day-1')
            ->click('[data-test="day-1-start-time"]')
            ->click('text=09:00')
            ->click('[data-test="day-1-end-time"]')
            ->click('text=17:00')
            ->assertSee('8.0'); // Should show calculated duration
    });

    it('preserves form data on validation error', function () {
        $this->actingAs($this->user);
        
        $page = visit('/admin/availabilities');
        
        $page->click('#day-1')
            ->click('[data-test="day-1-start-time"]')
            ->click('text=09:00')
            ->click('[data-test="day-1-end-time"]')
            ->click('text=16:45')
            ->click('#day-2')
            ->click('Save Changes')
            ->assertSee('Start time is required when day is active.')
            ->assertSee('End time is required when day is active.')
            ->assertSee('16:45');
    });

    it('can configure multiple days', function () {
        $this->actingAs($this->user);
        
        $page = visit('/admin/availabilities');
        
        // Configure Monday and Tuesday
        $page->click('#day-1')
            ->click('[data-test="day-1-start-time"]')
            ->click('text=09:00')
            ->click('[data-test="day-1-end-time"]')
            ->click('text=17:00')
            ->click('#day-2') // Tuesday  
            ->click('[data-test="day-1-start-time"]')
            ->click('text=10:30')
            ->click('[data-test="day-1-end-time"]')
            ->click('text=15:00')
            ->click('Save Changes')
            ->assertSee('2'); // 2 active days
    });
});