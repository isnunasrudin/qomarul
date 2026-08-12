<?php

use App\Models\Decree;
use App\Models\Employee;
use App\Models\WorkUnit;

beforeEach(function () {
    $this->unitA = WorkUnit::factory()->create(['code' => 'SMP']);
    $this->unitB = WorkUnit::factory()->create(['code' => 'SMK']);
});

it('lets a unit admin see only employees of its own unit', function () {
    Employee::factory()->create(['work_unit_id' => $this->unitA->id]);
    Employee::factory()->create(['work_unit_id' => $this->unitB->id]);

    actingAsRole('unitAdmin', ['work_unit_id' => $this->unitA->id]);

    $visible = Employee::query()->pluck('work_unit_id')->unique()->all();

    expect($visible)->toBe([$this->unitA->id]);
});

it('lets a unit admin not see employees of another unit even by direct id', function () {
    $employeeB = Employee::factory()->create(['work_unit_id' => $this->unitB->id]);

    actingAsRole('unitAdmin', ['work_unit_id' => $this->unitA->id]);

    expect(Employee::query()->find($employeeB->id))->toBeNull();
});

it('does not filter foundation admin', function () {
    Employee::factory()->create(['work_unit_id' => $this->unitA->id]);
    Employee::factory()->create(['work_unit_id' => $this->unitB->id]);

    actingAsRole('foundationAdmin');

    expect(Employee::query()->count())->toBe(2);
});

it('does not filter foundation head', function () {
    Employee::factory()->create(['work_unit_id' => $this->unitA->id]);
    Employee::factory()->create(['work_unit_id' => $this->unitB->id]);

    actingAsRole('foundationHead');

    expect(Employee::query()->count())->toBe(2);
});

it('lets a unit admin view decrees of its unit only', function () {
    $employeeA = Employee::factory()->create(['work_unit_id' => $this->unitA->id]);
    $employeeB = Employee::factory()->create(['work_unit_id' => $this->unitB->id]);

    Decree::factory()->create(['employee_id' => $employeeA->id, 'work_unit_id' => $this->unitA->id]);
    Decree::factory()->create(['employee_id' => $employeeB->id, 'work_unit_id' => $this->unitB->id]);

    actingAsRole('unitAdmin', ['work_unit_id' => $this->unitA->id]);

    expect(Decree::query()->pluck('work_unit_id')->all())->toBe([$this->unitA->id]);
});
