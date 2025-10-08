<?php

use App\Services\TimeSlotService;
use Carbon\Carbon;

beforeEach(function () {
    $this->generator = new TimeSlotService();
});

describe('TimeSlotService', function () {

    test('generates correct number of slots', function () {
        $slots = $this->generator->generateSlots('09:00', '17:00', 30, 60);

        // 8 hours (480 minutes) with 30min interval and 60min duration
        // Slots: 09:00, 09:30, 10:00, ..., 16:00
        // Total: 15 slots
        expect($slots)->toHaveCount(15);
    });

    test('generates slots with correct start times', function () {
        $slots = $this->generator->generateSlots('09:00', '11:00', 30, 60);

        expect($slots->pluck('start_time')->toArray())->toBe([
            '09:00',
            '09:30',
            '10:00',
        ]);
    });

    test('generates slots with correct end times', function () {
        $slots = $this->generator->generateSlots('09:00', '11:00', 30, 60);

        expect($slots->pluck('end_time')->toArray())->toBe([
            '10:00',
            '10:30',
            '11:00',
        ]);
    });

    test('respects slot duration', function () {
        $slots = $this->generator->generateSlots('09:00', '12:00', 60, 90);

        // Each slot should be 90 minutes
        foreach ($slots as $slot) {
            $start = Carbon::parse($slot['start_time']);
            $end = Carbon::parse($slot['end_time']);

            expect(intval($start->diffInMinutes($end)))->toBe(90);
        }
    });

    test('respects interval between slots', function () {
        $slots = $this->generator->generateSlots('09:00', '12:00', 45, 60)->toArray();

        // Intervals should be 45 minutes apart
        for ($i = 0; $i < count($slots) - 1; $i++) {
            $currentStart = Carbon::parse($slots[$i]['start_time']);
            $nextStart = Carbon::parse($slots[$i + 1]['start_time']);

            expect(intval($currentStart->diffInMinutes($nextStart)))->toBe(45);
        }
    });

    test('handles edge case where duration equals interval', function () {
        $slots = $this->generator->generateSlots('09:00', '12:00', 60, 60);

        // Non-overlapping slots
        expect($slots)->toHaveCount(3);
        expect($slots->pluck('start_time')->toArray())->toBe([
            '09:00',
            '10:00',
            '11:00',
        ]);
    });

    test('handles short time range', function () {
        $slots = $this->generator->generateSlots('09:00', '10:00', 30, 60);

        // Only one 60-minute slot fits in 1 hour
        expect($slots)->toHaveCount(1);
        expect($slots->first())->toBe([
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);
    });

    test('returns empty collection when duration exceeds range', function () {
        $slots = $this->generator->generateSlots('09:00', '09:30', 30, 60);

        // Cannot fit 60-minute slot in 30-minute range
        expect($slots)->toBeEmpty();
    });

    test('formats times with leading zeros', function () {
        $slots = $this->generator->generateSlots('08:00', '10:00', 30, 60);

        expect($slots->first()['start_time'])->toBe('08:00');
        expect($slots->first()['start_time'])->not->toBe('8:00');
    });

    test('handles time ranges spanning midday', function () {
        $slots = $this->generator->generateSlots('11:00', '14:00', 60, 60);

        expect($slots->pluck('start_time')->toArray())->toBe([
            '11:00',
            '12:00',
            '13:00',
        ]);
    });

    test('handles late evening slots', function () {
        $slots = $this->generator->generateSlots('20:00', '23:00', 60, 60);

        expect($slots)->toHaveCount(3);
        expect($slots->last()['start_time'])->toBe('22:00');
        expect($slots->last()['end_time'])->toBe('23:00');
    });

    test('handles early morning slots', function () {
        $slots = $this->generator->generateSlots('06:00', '09:00', 60, 60);

        expect($slots)->toHaveCount(3);
        expect($slots->first()['start_time'])->toBe('06:00');
    });
});
