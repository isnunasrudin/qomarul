<?php

use App\Models\AdditionalDuty;
use App\Models\AuditLog;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkUnit;

beforeEach(function () {
    $this->unit = WorkUnit::factory()->create();
    $this->employee = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    $this->position = Position::factory()->create();
    $this->duty = AdditionalDuty::factory()->create();
    $this->decreeType = DecreeType::factory()->create();

    $this->head = User::factory()->foundationHead()->create();
    $this->admin = User::factory()->foundationAdmin()->create();
    $this->unitAdmin = User::factory()->unitAdmin($this->unit->id)->create();
    $this->employeeUser = User::factory()->employee($this->employee->id)->create();
});

it('lets foundation admin manage master data', function () {
    $this->actingAs($this->admin);

    expect($this->admin->can('create', WorkUnit::class))->toBeTrue();
    expect($this->admin->can('update', $this->position))->toBeTrue();
    expect($this->admin->can('update', $this->decreeType))->toBeTrue();
    expect($this->admin->can('update', $this->duty))->toBeTrue();
});

it('gives foundation head read-only master data access', function () {
    $this->actingAs($this->head);

    expect($this->head->can('viewAny', WorkUnit::class))->toBeTrue();
    expect($this->head->can('create', WorkUnit::class))->toBeFalse();
    expect($this->head->can('update', $this->position))->toBeFalse();
});

it('denies master data access to unit admin', function () {
    $this->actingAs($this->unitAdmin);

    expect($this->unitAdmin->can('viewAny', WorkUnit::class))->toBeFalse();
    expect($this->unitAdmin->can('viewAny', Position::class))->toBeFalse();
    expect($this->unitAdmin->can('create', DecreeType::class))->toBeFalse();
});

it('denies master data access to employee', function () {
    $this->actingAs($this->employeeUser);

    expect($this->employeeUser->can('viewAny', WorkUnit::class))->toBeFalse();
    expect($this->employeeUser->can('viewAny', Position::class))->toBeFalse();
});

it('lets unit admin manage employees of its unit but not delete them', function () {
    $this->actingAs($this->unitAdmin);

    expect($this->unitAdmin->can('create', Employee::class))->toBeTrue();
    expect($this->unitAdmin->can('update', $this->employee))->toBeTrue();
    expect($this->unitAdmin->can('delete', $this->employee))->toBeFalse();
    expect($this->unitAdmin->can('updateNigy', $this->employee))->toBeFalse();
});

it('lets foundation admin manage employees and NIGY', function () {
    $this->actingAs($this->admin);

    expect($this->admin->can('create', Employee::class))->toBeTrue();
    expect($this->admin->can('delete', $this->employee))->toBeTrue();
    expect($this->admin->can('updateNigy', $this->employee))->toBeTrue();
});

it('lets employee see only himself', function () {
    $this->actingAs($this->employeeUser);

    expect($this->employeeUser->can('view', $this->employee))->toBeTrue();

    $other = Employee::factory()->create(['work_unit_id' => $this->unit->id]);
    expect($this->employeeUser->can('view', $other))->toBeFalse();
});

it('only foundation head may sign decrees', function () {
    $decree = Decree::factory()->create([
        'employee_id' => $this->employee->id,
        'work_unit_id' => $this->unit->id,
        'status' => 'verified',
    ]);

    $this->actingAs($this->unitAdmin);
    expect($this->unitAdmin->can('sign', $decree))->toBeFalse();

    $this->actingAs($this->admin);
    expect($this->admin->can('sign', $decree))->toBeFalse();

    $this->actingAs($this->head);
    expect($this->head->can('sign', $decree))->toBeTrue();
});

it('only foundation admin may manage users', function () {
    $this->actingAs($this->head);
    expect($this->head->can('create', User::class))->toBeFalse();

    $this->actingAs($this->admin);
    expect($this->admin->can('create', User::class))->toBeTrue();
});

it('only foundation roles may view audit logs', function () {
    $this->actingAs($this->head);
    expect($this->head->can('viewAny', AuditLog::class))->toBeTrue();

    $this->actingAs($this->admin);
    expect($this->admin->can('viewAny', AuditLog::class))->toBeTrue();

    $this->actingAs($this->unitAdmin);
    expect($this->unitAdmin->can('viewAny', AuditLog::class))->toBeFalse();
});

it('enforces roles via middleware on admin routes', function () {
    $this->actingAs($this->unitAdmin);

    $this->get('/admin/work-units')->assertStatus(403);

    $this->actingAs($this->employeeUser);
    $this->get('/admin/positions')->assertStatus(403);
});
