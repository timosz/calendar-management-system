<?php

use App\Models\Availability;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Availability Model', function () {
    it('belongs to user', function () {
        $availability = Availability::factory()->create(['user_id' => $this->user->id]);

        expect($availability->user)->toBeInstanceOf(User::class);
        expect($availability->user->id)->toBe($this->user->id);
    });

    it('has active scope', function () {
        Availability::factory()->create(['user_id' => $this->user->id, 'is_active' => true]);
        Availability::factory()->create(['user_id' => $this->user->id, 'is_active' => false]);

        expect(Availability::active()->count())->toBe(1);
    });

    it('provides day names', function () {
        $dayNames = Availability::getDayNames();

        expect($dayNames[0])->toBe('Sunday');
        expect($dayNames[1])->toBe('Monday');
    });

    it('calculates duration correctly', function () {
        $availability = Availability::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => '09:00',
            'end_time' => '17:30',
        ]);

        expect($availability->getDurationInHours())->toBe(8.5);
    });

    it('enforces unique constraint per user per day', function () {
        Availability::factory()->create([
            'user_id' => $this->user->id,
            'day_of_week' => 1,
        ]);

        expect(fn () => Availability::factory()->create([
            'user_id' => $this->user->id,
            'day_of_week' => 1,
        ]))->toThrow(UniqueConstraintViolationException::class);
    });
});
