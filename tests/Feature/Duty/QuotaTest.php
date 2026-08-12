<?php

use App\Models\AdditionalDuty;
use App\Models\Employee;
use App\Models\EmployeeAdditionalDuty;
use App\Models\User;
use App\Models\WorkUnit;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create();
    $this->duty = AdditionalDuty::factory()->create(['quota_per_unit' => 2, 'name' => 'Wali Kelas']);
    $this->admin = User::factory()->foundationAdmin()->create();
    $this->unitAdmin = User::factory()->unitAdmin($this->unit->id)->create();
});

it('rejects overlapping periods for the same duty and employee', function () {
    $employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);

    EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $employee->id,
        'additional_duty_id' => $this->duty->id,
        'work_unit_id' => $this->unit->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ]);

    $this->actingAs($this->admin)
        ->post('/admin/duties', [
            'employee_id' => $employee->id,
            'additional_duty_id' => $this->duty->id,
            'work_unit_id' => $this->unit->id,
            'academic_year' => '2026/2027',
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-30',
        ])
        ->assertSessionHasErrors('start_date');

    expect(EmployeeAdditionalDuty::count())->toBe(1);
});

it('allows a new period starting right after the previous one ends', function () {
    $employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);

    EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $employee->id,
        'additional_duty_id' => $this->duty->id,
        'work_unit_id' => $this->unit->id,
        'academic_year' => '2025/2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
    ]);

    $this->actingAs($this->admin)
        ->post('/admin/duties', [
            'employee_id' => $employee->id,
            'additional_duty_id' => $this->duty->id,
            'work_unit_id' => $this->unit->id,
            'academic_year' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ])
        ->assertSessionHasNoErrors();

    expect(EmployeeAdditionalDuty::count())->toBe(2);
});

it('rejects a second assignment of the same duty in the same academic year', function () {
    $employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);

    EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $employee->id,
        'additional_duty_id' => $this->duty->id,
        'work_unit_id' => $this->unit->id,
        'academic_year' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ]);

    $this->actingAs($this->admin)
        ->post('/admin/duties', [
            'employee_id' => $employee->id,
            'additional_duty_id' => $this->duty->id,
            'work_unit_id' => $this->unit->id,
            'academic_year' => '2026/2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
        ])
        ->assertSessionHasErrors('academic_year');

    expect(EmployeeAdditionalDuty::count())->toBe(1);
});

it('warns when the quota per unit is exceeded but still saves', function () {
    $dutyQuota = AdditionalDuty::factory()->create(['quota_per_unit' => 1, 'name' => 'Bendahara']);

    $e1 = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $e2 = Employee::factory()->create(['work_unit_id' => $this->unit->id]);

    $this->actingAs($this->admin)
        ->post('/admin/duties', [
            'employee_id' => $e1->id,
            'additional_duty_id' => $dutyQuota->id,
            'work_unit_id' => $this->unit->id,
            'academic_year' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->admin)
        ->post('/admin/duties', [
            'employee_id' => $e2->id,
            'additional_duty_id' => $dutyQuota->id,
            'work_unit_id' => $this->unit->id,
            'academic_year' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'peringatan'));

    expect(EmployeeAdditionalDuty::count())->toBe(2);
});

it('assigns a duty to many employees in one request, skipping conflicts', function () {
    $employees = Employee::factory()->count(4)->create(['work_unit_id' => $this->unit->id]);

    // satu GTK sudah memegang tugas pada periode beririsan
    EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $employees[0]->id,
        'additional_duty_id' => $this->duty->id,
        'work_unit_id' => $this->unit->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-08-31',
    ]);

    $this->actingAs($this->admin)
        ->post('/admin/duties/mass', [
            'employee_ids' => $employees->pluck('id')->all(),
            'additional_duty_id' => $this->duty->id,
            'work_unit_id' => $this->unit->id,
            'academic_year' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, '3 GTK ditetapkan') && str_contains($message, '1 GTK dilewati'));

    expect(EmployeeAdditionalDuty::count())->toBe(4);
});

it('scopes the duty list for unit admin to their own unit', function () {
    $otherUnit = WorkUnit::factory()->create();
    $e1 = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $e2 = Employee::factory()->create(['work_unit_id' => $otherUnit->id]);

    EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $e1->id,
        'additional_duty_id' => $this->duty->id,
        'work_unit_id' => $this->unit->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ]);
    EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $e2->id,
        'additional_duty_id' => $this->duty->id,
        'work_unit_id' => $otherUnit->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ]);

    $this->actingAs($this->unitAdmin)
        ->get('/admin/duties')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Duties/Index')
            ->has('assignments.data', 1));
});

it('lets employees see their own duties only', function () {
    $employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $colleague = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $employeeUser = User::factory()->employee($employee->id)->create();

    EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $employee->id,
        'additional_duty_id' => $this->duty->id,
        'work_unit_id' => $this->unit->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ]);
    EmployeeAdditionalDuty::factory()->create([
        'employee_id' => $colleague->id,
        'additional_duty_id' => $this->duty->id,
        'work_unit_id' => $this->unit->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ]);

    $this->actingAs($employeeUser)
        ->get('/portal')
        ->assertInertia(fn ($page) => $page->component('Portal/Home')
            ->has('activeDuties', 1));
});
