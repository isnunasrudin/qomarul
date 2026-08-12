<?php

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkUnit;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create();
    $this->otherUnit = WorkUnit::factory()->create();
    $this->position = Position::factory()->create();
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->employeeUser = User::factory()->employee($this->employee->id)->create();
});

it('ignores administrative fields sent through the profile update', function () {
    $this->actingAs($this->employeeUser)
        ->put('/portal/profile', [
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka 12',
            // field administratif yang dicoba diselundupkan:
            'nigy' => 'HACKED001',
            'work_unit_id' => $this->otherUnit->id,
            'position_id' => $this->position->id,
            'employment_status_id' => 999,
            'foundation_start_date' => '2000-01-01',
            'unit_start_date' => '2000-01-01',
            'is_active' => false,
            'name' => 'Nama Baru',
        ])
        ->assertSessionHasNoErrors();

    $employee = $this->employee->fresh();

    expect($employee->phone)->toBe('081234567890');
    expect($employee->address)->toBe('Jl. Merdeka 12');
    expect($employee->nigy)->toBe($this->employee->nigy);
    expect($employee->work_unit_id)->toBe($this->unit->id);
    expect($employee->position_id)->toBe($this->employee->position_id);
    expect($employee->name)->toBe($this->employee->name);
    expect($employee->is_active)->toBeTrue();
});

it('keeps the employees NIGY untouched after profile updates', function () {
    $this->actingAs($this->employeeUser)
        ->put('/portal/profile', ['phone' => '082233445566']);

    expect($this->employee->fresh()->nigy)->toBe($this->employee->nigy);
});
