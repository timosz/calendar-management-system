<?php

use App\Models\Restriction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Restriction Model', function () {
    it('belongs to user', function () {
        $restriction = Restriction::factory()->create(['user_id' => $this->user->id]);

        expect($restriction->user)->toBeInstanceOf(User::class);
        expect($restriction->user->id)->toBe($this->user->id);
    });

    it('has ofType scope', function () {
        Restriction::factory()->create(['user_id' => $this->user->id, 'type' => 'holiday']);
        Restriction::factory()->create(['user_id' => $this->user->id, 'type' => 'break']);
        Restriction::factory()->create(['user_id' => $this->user->id, 'type' => 'holiday']);

        expect(Restriction::ofType('holiday')->count())->toBe(2);
    });

    it('provides restriction types', function () {
        $types = Restriction::getTypes();

        expect($types)->toBeArray();
        expect($types)->toHaveKey('holiday');
        expect($types)->toHaveKey('break');
        expect($types)->toHaveKey('meeting');
    });

    it('identifies all-day restrictions', function () {
        $allDay = Restriction::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => null,
            'end_time' => null,
        ]);

        $partial = Restriction::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        expect($allDay->isAllDay())->toBeTrue();
        expect($partial->isAllDay())->toBeFalse();
    });

    it('casts dates correctly', function () {
        $restriction = Restriction::factory()->create([
            'user_id' => $this->user->id,
            'start_date' => '2025-01-15',
            'end_date' => '2025-01-17',
        ]);

        expect($restriction->start_date)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($restriction->end_date)->toBeInstanceOf(\Carbon\Carbon::class);
    });
});
