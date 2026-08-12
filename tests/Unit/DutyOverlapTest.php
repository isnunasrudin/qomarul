<?php

use App\Models\AdditionalDuty;
use App\Models\Employee;
use App\Models\EmployeeAdditionalDuty;
use App\Services\Duty\DutyOverlapService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = new DutyOverlapService;
});

it('detects full overlap', function () {
    expect($this->service->rangesOverlap(
        Carbon::parse('2026-07-15'), Carbon::parse('2026-09-15'),
        Carbon::parse('2026-07-01'), Carbon::parse('2026-12-31'),
    ))->toBeTrue();
});

it('detects partial overlap', function () {
    expect($this->service->rangesOverlap(
        Carbon::parse('2026-06-01'), Carbon::parse('2026-08-31'),
        Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'),
    ))->toBeTrue();
});

it('treats ranges touching at the boundary as non-overlapping', function () {
    // berakhir 30 Juni, yang baru mulai 1 Juli — boleh
    expect($this->service->rangesOverlap(
        Carbon::parse('2026-07-01'), Carbon::parse('2026-12-31'),
        Carbon::parse('2026-01-01'), Carbon::parse('2026-06-30'),
    ))->toBeFalse();

    // mulai di hari yang sama saat yang lama berakhir → irisan (kedua ujung sama)
    expect($this->service->rangesOverlap(
        Carbon::parse('2026-07-01'), Carbon::parse('2026-12-31'),
        Carbon::parse('2026-01-01'), Carbon::parse('2026-07-01'),
    ))->toBeTrue();
});

it('detects no overlap for fully separated ranges', function () {
    expect($this->service->rangesOverlap(
        Carbon::parse('2026-09-01'), Carbon::parse('2026-12-31'),
        Carbon::parse('2026-01-01'), Carbon::parse('2026-06-30'),
    ))->toBeFalse();
});

it('finds an overlapping assignment in the database', function () {
    $employee = Employee::factory()->create();
    $duty = AdditionalDuty::factory()->create();

    EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $employee->id,
        'additional_duty_id' => $duty->id,
        'work_unit_id' => $employee->work_unit_id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ]);

    $found = $this->service->findOverlapping(
        $employee->id,
        $duty->id,
        Carbon::parse('2026-08-01'),
        Carbon::parse('2026-09-30'),
    );

    expect($found)->not->toBeNull();
});

it('does not treat the assignment being edited as its own conflict', function () {
    $employee = Employee::factory()->create();
    $duty = AdditionalDuty::factory()->create();

    $assignment = EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $employee->id,
        'additional_duty_id' => $duty->id,
        'work_unit_id' => $employee->work_unit_id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ]);

    $found = $this->service->findOverlapping(
        $employee->id,
        $duty->id,
        Carbon::parse('2026-08-01'),
        Carbon::parse('2026-09-30'),
        ignoreAssignmentId: $assignment->id,
    );

    expect($found)->toBeNull();
});
