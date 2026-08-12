<?php

use App\Models\Decree;
use App\Models\Document;
use App\Models\Employee;
use App\Models\WorkUnit;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create();
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->colleague = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
});

it('restricts employee queries to the employee itself', function () {
    actingAsRole('employee', ['employee_id' => $this->employee->id]);

    $ids = Employee::query()->pluck('id')->all();

    expect($ids)->toBe([$this->employee->id]);
});

it('cannot see a colleague even by direct id, in the same unit', function () {
    actingAsRole('employee', ['employee_id' => $this->employee->id]);

    expect(Employee::query()->find($this->colleague->id))->toBeNull();
});

it('cannot see colleagues documents even by direct id', function () {
    Document::factory()->create(['employee_id' => $this->employee->id]);
    Document::factory()->create(['employee_id' => $this->colleague->id]);

    actingAsRole('employee', ['employee_id' => $this->employee->id]);

    expect(Document::query()->pluck('employee_id')->all())->toBe([$this->employee->id]);
});

it('restricts decree queries to own decrees', function () {
    $ownDecree = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
    ]);
    Decree::factory()->create([
        'employee_id' => $this->colleague->id,
        'work_unit_id' => $this->unit->id,
    ]);

    actingAsRole('employee', ['employee_id' => $this->employee->id]);

    expect(Decree::query()->pluck('id')->all())->toBe([$ownDecree->id]);
});

it('denies access to a colleagues decree through the policy layer', function () {
    $colleagueDecree = Decree::factory()->create([
        'employee_id' => $this->colleague->id,
        'work_unit_id' => $this->unit->id,
        'status' => 'issued',
    ]);

    actingAsRole('employee', ['employee_id' => $this->employee->id]);

    expect(auth()->user()->can('view', $colleagueDecree))->toBeFalse();
});
