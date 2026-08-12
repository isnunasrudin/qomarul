<?php

use App\Enums\DecreeStatus;
use App\Enums\Gender;
use App\Models\AuditLog;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkUnit;

beforeEach(function () {
    $this->workUnit = WorkUnit::factory()->create(['code' => 'SMK']);
    $this->positionId = Position::factory()->create()->id;
    $this->statusId = EmploymentStatus::factory()->create()->id;
    $this->admin = User::factory()->foundationAdmin()->create();
    $this->unitAdmin = User::factory()->unitAdmin($this->workUnit->id)->create();
});

it('assigns an automatic NIGY when a new employee is stored', function () {
    $this->actingAs($this->admin)
        ->post('/admin/employees', payload())
        ->assertRedirect();

    $employee = Employee::firstOrFail();

    expect($employee->nigy)->toBe('2026SMK001');
});

it('keeps the sequence per work unit and year', function () {
    $this->actingAs($this->admin)->post('/admin/employees', payload());
    $this->actingAs($this->admin)->post('/admin/employees', payload());

    expect(Employee::orderBy('id')->pluck('nigy')->all())->toBe(['2026SMK001', '2026SMK002']);
});

it('lets foundation admin override NIGY manually', function () {
    $this->actingAs($this->admin)->post('/admin/employees', payload());

    $employee = Employee::firstOrFail();

    $this->actingAs($this->admin)
        ->put("/admin/employees/{$employee->id}", [
            ...payload(),
            'nigy' => '2020SMK1999',
            'nigy_reason' => 'Menyesuaikan NIGY lama',
        ])
        ->assertSessionHasNoErrors();

    expect($employee->fresh()->nigy)->toBe('2020SMK1999');
});

it('ignores nigy field submitted by unit admin', function () {
    $this->actingAs($this->admin)->post('/admin/employees', payload());

    $employee = Employee::firstOrFail();

    $this->actingAs($this->unitAdmin)
        ->put("/admin/employees/{$employee->id}", [
            ...payload(),
            'nigy' => 'HACKED001',
        ])
        ->assertSessionHasNoErrors();

    expect($employee->fresh()->nigy)->toBe('2026SMK001');
});

it('records NIGY override in the audit log with reason', function () {
    $this->actingAs($this->admin)->post('/admin/employees', payload());

    $employee = Employee::firstOrFail();

    $this->actingAs($this->admin)
        ->put("/admin/employees/{$employee->id}", [
            ...payload(),
            'nigy' => '2020SMK1999',
            'nigy_reason' => 'Sesuai buku induk',
        ]);

    $log = AuditLog::query()
        ->where('auditable_type', Employee::class)
        ->where('auditable_id', $employee->id)
        ->where('action', 'nigy_override')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->old_values['nigy'])->toBe('2026SMK001');
    expect($log->new_values['nigy'])->toBe('2020SMK1999');
    expect($log->new_values['reason'])->toBe('Sesuai buku induk');
});

it('locks NIGY once an issued decree carries it', function () {
    $this->actingAs($this->admin)->post('/admin/employees', payload());

    $employee = Employee::firstOrFail();

    Decree::factory()->create([
        'employee_id' => $employee->id,
        'work_unit_id' => $this->workUnit->id,
        'decree_type_id' => DecreeType::factory()->create()->id,
        'status' => DecreeStatus::Issued,
        'decree_number' => '042/SK-PPT/SMK/YPP-QH/VII/2026',
    ]);

    $this->actingAs($this->admin)
        ->put("/admin/employees/{$employee->id}", [
            ...payload(),
            'nigy' => '2020SMK1999',
            'nigy_reason' => 'coba ubah',
        ])
        ->assertSessionHasErrors('nigy');

    expect($employee->fresh()->nigy)->toBe('2026SMK001');
});

it('mentions the locking decree numbers in the error message', function () {
    $this->actingAs($this->admin)->post('/admin/employees', payload());

    $employee = Employee::firstOrFail();

    Decree::factory()->create([
        'employee_id' => $employee->id,
        'work_unit_id' => $this->workUnit->id,
        'decree_type_id' => DecreeType::factory()->create()->id,
        'status' => DecreeStatus::Issued,
        'decree_number' => '007/SK-PPT/SMK/YPP-QH/VII/2026',
    ]);

    $response = $this->actingAs($this->admin)
        ->put("/admin/employees/{$employee->id}", [
            ...payload(),
            'nigy' => '2020SMK1999',
        ]);

    $response->assertSessionHasErrors('nigy');

    $errors = $response->getSession()->get('errors');
    $message = is_object($errors)
        ? $errors->first('nigy')
        : (collect($errors['default']['messages']['nigy'] ?? [])->first() ?? '');

    expect($message)->toContain('007/SK-PPT/SMK/YPP-QH/VII/2026');
});

it('does not change NIGY when the work unit is changed', function () {
    $this->actingAs($this->admin)->post('/admin/employees', payload());

    $employee = Employee::firstOrFail();
    $otherUnit = WorkUnit::factory()->create(['code' => 'SD1']);

    $this->actingAs($this->admin)
        ->put("/admin/employees/{$employee->id}", [
            ...payload(),
            'work_unit_id' => $otherUnit->id,
        ])
        ->assertSessionHasNoErrors();

    expect($employee->fresh()->nigy)->toBe('2026SMK001');
    expect($employee->fresh()->work_unit_id)->toBe($otherUnit->id);
});

it('scopes employee listings to the unit admin own unit', function () {
    $this->actingAs($this->admin)->post('/admin/employees', payload());

    $this->actingAs($this->unitAdmin)
        ->get('/admin/employees')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Employees/Index')
            ->has('employees.data', 1));
});

it('lets unit admin create employees in their own unit only', function () {
    $this->actingAs($this->unitAdmin)
        ->post('/admin/employees', payload())
        ->assertRedirect();
});

it('rejects duplicate NIGY values on manual override', function () {
    $this->actingAs(test()->admin)->post('/admin/employees', payload());
    $first = Employee::firstOrFail();

    $this->actingAs(test()->admin)->post('/admin/employees', [
        ...payload(),
        'nigy' => 'MANUAL001',
    ]);

    $second = Employee::orderByDesc('id')->firstOrFail();

    $this->actingAs(test()->admin)
        ->put("/admin/employees/{$second->id}", [
            ...payload(),
            'nigy' => $first->nigy,
        ])
        ->assertSessionHasErrors('nigy');
});

/** @return array<string, mixed> */
function payload(): array
{
    return [
        'name' => 'Budi Santoso, S.Pd.',
        'gender' => Gender::Male->value,
        'birth_place' => 'Trenggalek',
        'birth_date' => '1990-04-15',
        'work_unit_id' => test()->workUnit->id,
        'position_id' => test()->positionId,
        'employment_status_id' => test()->statusId,
        'foundation_start_date' => '2026-07-01',
        'is_active' => true,
    ];
}
