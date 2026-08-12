<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Employee\ProfileCompletenessService;

it('renders the employee show page with education and documents', function () {
    $unit = WorkUnit::factory()->create();
    $employee = Employee::factory()->create(['work_unit_id' => $unit->id]);
    $admin = User::factory()->foundationAdmin()->create();

    $this->actingAs($admin)
        ->get("/admin/employees/{$employee->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Employees/Show')
            ->where('completeness.complete', false)
            ->has('completeness.missing')
            ->has('employee.educations')
            ->has('employee.documents'));
});

it('renders the employee edit form', function () {
    $unit = WorkUnit::factory()->create();
    $employee = Employee::factory()->create(['work_unit_id' => $unit->id]);
    $admin = User::factory()->foundationAdmin()->create();

    $this->actingAs($admin)
        ->get("/admin/employees/{$employee->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Employees/Form')
            ->where('nigyLocked', false));
});

it('computes profile completeness as the employee adds data', function () {
    $unit = WorkUnit::factory()->create();
    $employee = Employee::factory()->create(['work_unit_id' => $unit->id]);

    $completeness = app(ProfileCompletenessService::class)->evaluate($employee);

    expect($completeness['complete'])->toBeFalse();
    expect($completeness['missing'])->toContain('pribadi.nik');
    expect($completeness['missing'])->toContain('pendidikan.tertinggi');
    expect($completeness['missing'])->toContain('berkas.ktp');
});
