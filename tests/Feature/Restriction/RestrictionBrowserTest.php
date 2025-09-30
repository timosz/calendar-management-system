<?php

use App\Models\Booking;
use App\Models\Restriction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);

    // Helper function to select a date in vue-datepicker
    $this->selectDateInCalendar = function ($page, $inputSelector, $daysAhead = 0) {
        // Find the first date without any bookings
        $today = now();
        $firstAvailableDate = null;

        // Check up to 60 days ahead
        for ($i = $daysAhead; $i < 60; $i++) {
            $checkDate = $today->copy()->addDays($i);

            // Check if this date has no bookings
            $hasBookings = Booking::query()
                ->where('user_id', $this->user->id)
                ->where('booking_date', $checkDate->format('Y-m-d'))
                ->exists();

            if (!$hasBookings) {
                $firstAvailableDate = $checkDate;
                break;
            }
        }

        // If no available date found, use a date far in the future
        if (!$firstAvailableDate) {
            $firstAvailableDate = $today->copy()->addMonths(6)->addDays($daysAhead);
        }

        // Click the input to open the calendar
        $page->click($inputSelector)
            ->wait(0.5);

        // Calculate how many months to navigate forward
        $currentMonth = now();
        $targetMonth = $firstAvailableDate->copy()->startOfMonth();
        $monthsToNavigate = $currentMonth->diffInMonths($targetMonth);

        // Navigate to the correct month using the next arrow
        for ($i = 0; $i < $monthsToNavigate; $i++) {
            $page->click('.dp__arrow_top, .dp__arrow_right')
                ->wait(0.3);
        }

        // Format the data-test-id (e.g., "dp-2025-09-30")
        $dataTestId = 'dp-' . $firstAvailableDate->format('Y-m-d');

        $page->click("[data-test-id=\"{$dataTestId}\"]")
            ->wait(0.3);

        return $firstAvailableDate;
    };
});

describe('Restriction Interface', function () {
    it('can create all-day restriction', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/restrictions/create');

        $page->assertSee('Create Restriction')
            ->click('type')
            ->click('Holiday');

        // Select start date - target the input field
        $startDate = ($this->selectDateInCalendar)($page, 'input[placeholder*="start"]');

        // Select end date
        ($this->selectDateInCalendar)($page, 'input[placeholder*="end"]');

        $page->check('all_day')
            ->type('reason', 'Vacation week')
            ->click('button[type="submit"]', 'Create Restriction')
            ->assertSee('Restriction created successfully');
    });

    it('can create partial-day restriction', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/restrictions/create');

        $page->assertSee('Create Restriction')
            ->click('type')
            ->click('Break');

        // Select start date
        ($this->selectDateInCalendar)($page, 'input[placeholder*="start"]');

        // Select end date
        ($this->selectDateInCalendar)($page, 'input[placeholder*="end"]');

        $page->click('div#start_time > button:first-child')
            ->click('text=12:00')
            ->click('div#end_time > button:first-child')
            ->click('text=13:00')
            ->type('reason', 'Lunch break')
            ->click('button[type="submit"]', 'Create Restriction')
            ->assertSee('Restriction created successfully');
    });

    it('validates restriction form', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/restrictions/create');

        $page->click('button[type="submit"]', 'Create Restriction')
            ->wait(0.5)
            ->assertSee('The start date field is required.')
            ->assertSee('The end date field is required.');
    });

    it('shows all-day toggle behavior', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/restrictions/create');

        $page->check('all_day')
            ->assertMissing('[data-test="start-time"]');
    });

    it('can filter restrictions by type', function () {
        $this->actingAs($this->user);

        Restriction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'holiday',
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-05',
        ]);

        Restriction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'break',
            'start_date' => '2025-10-10',
            'end_date' => '2025-10-10',
        ]);

        $page = visit('/admin/restrictions');

        $page->click('text=Filters')
            ->click('#filter-type')
            ->click('Holiday')
            ->click('Apply Filters')
            ->assertSee('Holiday')
            ->assertDontSee('Break');
    });

    it('can edit restriction', function () {
        $this->actingAs($this->user);

        $restriction = Restriction::factory()->create([
            'user_id' => $this->user->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'type' => 'break',
            'reason' => 'Original reason',
        ]);

        $page = visit("/admin/restrictions/{$restriction->id}/edit");

        $page->assertSee('Edit Restriction')
            ->assertValue('reason', 'Original reason');

        // Select new start date
        $startDate = ($this->selectDateInCalendar)($page, 'input[placeholder*="start"]');

        // Select new end date
        $endDate = ($this->selectDateInCalendar)($page, 'input[placeholder*="end"]');

        $page->type('reason', 'Updated reason')
            ->click('button[type="submit"]', 'Update Restriction')
            ->assertSee('Restriction updated successfully');
    });

    it('can delete restriction', function () {
        $this->actingAs($this->user);

        $restriction = Restriction::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $page = visit('/admin/restrictions');

        $page->click('button[data-test="delete-button"]')
            ->assertSee('Are you sure?')
            ->click('Delete')
            ->assertSee('Restriction deleted successfully');
    });

    it('shows correct type badges', function () {
        $this->actingAs($this->user);

        Restriction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'holiday',
        ]);

        Restriction::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'meeting',
        ]);

        $page = visit('/admin/restrictions');

        $page->assertSee('Holiday')
            ->assertSee('Meeting');
    });

    it('displays empty state when no restrictions', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/restrictions');

        $page->assertSee('No restrictions found')
            ->assertSee('Get started by creating a new restriction');
    });
});
