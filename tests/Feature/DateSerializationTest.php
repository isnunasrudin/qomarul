<?php

use App\Models\Decree;
use App\Models\Employee;
use App\Models\EmployeeAdditionalDuty;

it('menyertakan tanggal sebagai Y-m-d tanpa pergeseran zona waktu', function () {
    $employee = Employee::factory()->create([
        'birth_date' => '2026-08-07',
        'foundation_start_date' => '2020-06-24',
        'unit_start_date' => '2020-06-24',
    ]);

    $payload = json_decode($employee->toJson(), true);

    expect($payload['birth_date'])->toBe('2026-08-07');
    expect($payload['foundation_start_date'])->toBe('2020-06-24');
    expect($payload['unit_start_date'])->toBe('2020-06-24');
});

it('tidak menggeser tanggal pada decree dan tugas tambahan', function () {
    $decree = Decree::factory()->create([
        'effective_date' => '2026-08-01',
        'issued_date' => '2026-08-05',
    ]);
    $duty = EmployeeAdditionalDuty::factory()->create([
        'start_date' => '2026-07-15',
        'end_date' => '2027-06-30',
    ]);

    $decreePayload = json_decode($decree->toJson(), true);
    $dutyPayload = json_decode($duty->toJson(), true);

    expect($decreePayload['effective_date'])->toBe('2026-08-01');
    expect($decreePayload['issued_date'])->toBe('2026-08-05');
    expect($dutyPayload['start_date'])->toBe('2026-07-15');
    expect($dutyPayload['end_date'])->toBe('2027-06-30');
});
